<?php

namespace MiningManager\Services\Tax\Ore;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MiningManager\Models\OreContract;
use MiningManager\Models\PaymentAllocation;
use MiningManager\Models\TaxCode;
use MiningManager\Services\Pricing\OreValuationService;
use MiningManager\Services\Tax\PaymentAllocationService;
use MiningManager\Services\Tax\WalletTransferService;

/**
 * Credits mining tax paid in ore.
 *
 * In ore mode a member pays with an item exchange contract to the
 * corporation holding the ore their bill lists. SeAT fetches the
 * corporation's contracts and their items (it needs a director's token with
 * the corporation contracts scope); this reads them and, once a contract is
 * accepted, credits it:
 *
 *  - The bill: the tax code in the contract's description picks it, as for
 *    wallet payments. Without a code the contract pays the member's oldest
 *    open bills first.
 *  - The value: each ore is worth, per unit, what it is worth on the
 *    member's bill, so handing in exactly the listed ore pays exactly the
 *    bill. Compressed ore counts unit for unit as the raw ore. Ore that is on
 *    none of their bills is valued the way the ledger values mining.
 *  - The money: it goes through PaymentAllocationService like any payment:
 *    the bill first, then older open bills, and anything left is held as
 *    balance for the next bill.
 *
 * Contracts issued before ore mode was turned on for the corporation are left
 * alone. Each contract is recorded once in mining_manager_ore_contracts and
 * claimed once in the payment ledger, so it can never be credited twice.
 */
class OreContractService
{
    private const ACCEPTED = ['finished', 'finished_issuer', 'finished_contractor'];
    private const REFUSED = ['cancelled', 'rejected', 'failed', 'deleted', 'reversed'];

    /** How long an accepted contract may wait for SeAT to fetch its items. */
    private const ITEMS_GRACE_DAYS = 3;

    /**
     * How long an accepted contract with no tax code waits for its issuer to
     * have a bill before it is set aside as not tax.
     */
    private const NO_BILL_GRACE_DAYS = 35;

    public function __construct(
        private OreTaxService $ore,
        private PaymentAllocationService $allocations,
        private WalletTransferService $wallet,
        private OreValuationService $valuation
    ) {
    }

    /**
     * Check every corporation that takes tax in ore.
     *
     * @return array{corporations:int, seen:int, waiting:int, credited:int, refused:int, empty:int, ignored:int, isk:float}
     */
    public function check(bool $dryRun = false): array
    {
        $summary = ['corporations' => 0, 'seen' => 0, 'waiting' => 0, 'credited' => 0, 'refused' => 0, 'empty' => 0, 'ignored' => 0, 'isk' => 0.0];

        foreach ($this->ore->oreCorporations() as $corporationId => $since) {
            $summary['corporations']++;

            foreach ($this->openContracts($corporationId, $since) as $contract) {
                $summary['seen']++;
                $outcome = $this->process($corporationId, $contract, $dryRun);
                $summary[$outcome['status']] = ($summary[$outcome['status']] ?? 0) + 1;
                $summary['isk'] += $outcome['isk'] ?? 0;
            }
        }

        return $summary;
    }

    /**
     * Item exchange contracts to the corporation, issued since ore mode began,
     * that are not settled in our records yet.
     */
    private function openContracts(int $corporationId, Carbon $since)
    {
        $settled = OreContract::where('corporation_id', $corporationId)
            ->whereIn('status', [OreContract::CREDITED, OreContract::REFUSED, OreContract::EMPTY, OreContract::IGNORED])
            ->pluck('contract_id')
            ->all();

        return DB::table('corporation_contracts as cc')
            ->join('contract_details as d', 'd.contract_id', '=', 'cc.contract_id')
            ->where('cc.corporation_id', $corporationId)
            ->where('d.assignee_id', $corporationId)
            ->where('d.type', 'item_exchange')
            ->where('d.issuer_id', '!=', $corporationId)
            // The corporation pays nothing for tax. A contract with a price
            // is a sale to the corporation (a buyback), not tax.
            ->where(fn ($q) => $q->whereNull('d.price')->orWhere('d.price', '<=', 0))
            ->where('d.date_issued', '>=', $since)
            ->when($settled, fn ($q) => $q->whereNotIn('d.contract_id', $settled))
            ->orderBy('d.date_issued')
            ->get([
                'd.contract_id', 'd.issuer_id', 'd.status', 'd.title', 'd.price',
                'd.date_issued', 'd.date_expired', 'd.date_completed',
            ]);
    }

    /**
     * @return array{status:string, isk?:float}
     */
    public function process(int $corporationId, object $contract, bool $dryRun = false): array
    {
        $contractId = (int) $contract->contract_id;
        $issuerId = (int) $contract->issuer_id;
        $code = TaxCode::extractCodeFromText((string) ($contract->title ?? ''));
        $taxCode = $code ? $this->wallet->resolveTaxCodeRecord($code, $issuerId) : null;
        $primaryTaxId = $taxCode ? (int) $taxCode->mining_tax_id : null;

        $base = [
            'corporation_id' => $corporationId,
            'issuer_id' => $issuerId,
            'contract_status' => (string) $contract->status,
            'title' => $contract->title ? mb_substr((string) $contract->title, 0, 255) : null,
            'tax_code' => $code,
            'mining_tax_id' => $primaryTaxId,
            'date_issued' => $contract->date_issued,
            'date_completed' => $contract->date_completed,
        ];

        $items = $this->items($contractId);
        $priced = $items ? $this->price($items, $issuerId, $primaryTaxId) : ['value' => 0.0, 'lines' => []];
        $base['items'] = array_values($priced['lines']);
        $base['value'] = $priced['value'];

        $expired = $contract->status === 'outstanding'
            && $contract->date_expired
            && Carbon::parse($contract->date_expired)->isPast();

        if (in_array($contract->status, self::REFUSED, true) || $expired) {
            $this->save($contractId, $base + [
                'status' => OreContract::REFUSED,
                'notes' => $expired ? 'Expired without being accepted.' : 'The contract was ' . $contract->status . ' in game.',
            ], $dryRun);

            return ['status' => 'refused'];
        }

        if (!in_array($contract->status, self::ACCEPTED, true)) {
            // Outstanding: shown to the member and to directors as waiting.
            $this->save($contractId, $base + ['status' => OreContract::WAITING, 'notes' => null], $dryRun);

            return ['status' => 'waiting'];
        }

        // Accepted. SeAT fetches a contract's items separately; give it time.
        if (!$items) {
            $completed = $contract->date_completed ? Carbon::parse($contract->date_completed) : Carbon::now();
            if ($completed->diffInDays(Carbon::now()) < self::ITEMS_GRACE_DAYS) {
                $this->save($contractId, $base + ['status' => OreContract::WAITING, 'notes' => 'Accepted; waiting for SeAT to fetch its items.'], $dryRun);

                return ['status' => 'waiting'];
            }

            $this->save($contractId, $base + ['status' => OreContract::EMPTY, 'notes' => 'Accepted, but SeAT never fetched its items.'], $dryRun);

            return ['status' => 'empty'];
        }

        if ($priced['value'] <= 0) {
            $this->save($contractId, $base + ['status' => OreContract::EMPTY, 'notes' => 'Accepted, but nothing in it could be valued.'], $dryRun);

            return ['status' => 'empty'];
        }

        // Without a tax code, a contract is only tax if its issuer has a bill
        // to pay. Ore handed over for anything else (hauling, a gift) would
        // otherwise turn into balance. Bills are raised after the period
        // closes, so it waits a while for one before being set aside.
        if (!$primaryTaxId && !$this->hasOpenBill($issuerId)) {
            $completed = $contract->date_completed ? Carbon::parse($contract->date_completed) : Carbon::now();
            if ($completed->diffInDays(Carbon::now()) < self::NO_BILL_GRACE_DAYS) {
                $this->save($contractId, $base + ['status' => OreContract::WAITING, 'notes' => 'Accepted; no tax code and no open bill yet.'], $dryRun);

                return ['status' => 'waiting'];
            }

            $this->save($contractId, $base + ['status' => OreContract::IGNORED, 'notes' => 'No tax code, and its pilot had no bill to pay, so it was not treated as tax.'], $dryRun);

            return ['status' => 'ignored'];
        }

        if ($dryRun) {
            return ['status' => 'credited', 'isk' => $priced['value']];
        }

        $result = $this->allocations
            ->setCorporationContext($corporationId)
            ->allocate((object) [
                'id' => OreContract::paymentIdFor($contractId),
                'amount' => $priced['value'],
                'date' => $contract->date_completed ? Carbon::parse($contract->date_completed) : Carbon::now(),
                'first_party_id' => $issuerId,
            ], $primaryTaxId, [
                'source' => PaymentAllocation::SOURCE_CONTRACT,
                'notes' => 'Ore contract ' . $contractId . ': ' . $this->describe($priced['lines']),
                'cascade' => true,
                'hold_credit' => true,
            ]);

        if ($result['reason'] === 'already_claimed') {
            // Credited by an earlier run that stopped before recording it.
            $this->save($contractId, $base + ['status' => OreContract::CREDITED, 'notes' => 'Already credited.'], false);

            return ['status' => 'credited'];
        }

        if (!$result['applied'] && $result['credited'] <= 0) {
            $this->save($contractId, $base + [
                'status' => OreContract::WAITING,
                'notes' => 'Accepted, but could not be credited yet (' . ($result['reason'] ?? 'unknown') . ').',
            ], false);

            return ['status' => 'waiting'];
        }

        $credited = round(array_sum(array_column($result['allocations'], 'amount')), 2);

        $this->save($contractId, $base + [
            'status' => OreContract::CREDITED,
            'mining_tax_id' => $result['claimed_tax_id'] ?? $primaryTaxId,
            'credited' => $credited,
            'held' => (float) $result['credited'],
            'credited_at' => Carbon::now(),
            'notes' => null,
        ], false);

        Log::info('Mining Manager: credited an ore tax contract', [
            'contract_id' => $contractId,
            'issuer_id' => $issuerId,
            'value' => $priced['value'],
            'to_bills' => $credited,
            'held' => $result['credited'],
        ]);

        return ['status' => 'credited', 'isk' => $priced['value']];
    }

    /**
     * Units handed in per raw ore type.
     *
     * @return array<int, int> raw type_id => units
     */
    private function items(int $contractId): array
    {
        $rows = DB::table('contract_items')
            ->where('contract_id', $contractId)
            ->where('is_included', true)
            ->get(['type_id', 'quantity']);

        if ($rows->isEmpty()) {
            return [];
        }

        $raw = $this->ore->rawTypes($rows->pluck('type_id')->all());
        $units = [];
        foreach ($rows as $row) {
            $type = $raw[(int) $row->type_id] ?? (int) $row->type_id;
            $units[$type] = ($units[$type] ?? 0) + (int) $row->quantity;
        }

        return $units;
    }

    private function price(array $items, int $issuerId, ?int $primaryTaxId): array
    {
        $unitValues = $this->ore->unitValuesFor($issuerId, $primaryTaxId);

        $priced = OreMath::valueHandedIn($items, $unitValues, function (int $typeId) {
            try {
                $values = $this->valuation->calculateOreValue($typeId, 1000);
                $total = (float) ($values['total_value'] ?? 0);

                return $total > 0 ? $total / 1000 : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        $names = $this->ore->typeNames(array_keys($priced['lines']));
        foreach ($priced['lines'] as $typeId => &$line) {
            $line['name'] = $names[$typeId] ?? "Type {$typeId}";
        }
        unset($line);

        return $priced;
    }

    private function hasOpenBill(int $characterId): bool
    {
        return \MiningManager\Models\MiningTax::whereIn('character_id', $this->ore->accountCharacterIds($characterId))
            ->outstanding()
            ->exists();
    }

    private function describe(array $lines): string
    {
        $parts = [];
        foreach ($lines as $line) {
            $parts[] = number_format($line['units']) . ' ' . ($line['name'] ?? 'Type ' . $line['type_id']);
        }

        return implode(', ', array_slice($parts, 0, 6)) . (count($parts) > 6 ? ', ...' : '');
    }

    private function save(int $contractId, array $attributes, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        OreContract::updateOrCreate(['contract_id' => $contractId], $attributes);
    }
}
