<?php

namespace MiningManager\Services\Tax\Ore;

use Illuminate\Support\Facades\Log;
use MiningManager\Models\MiningTax;
use MiningManager\Models\OreContract;

/**
 * Ore owed and ore paid, by ore type, for the tax pages.
 *
 * Owed is the ore still to hand in on open bills (OreTaxService::linesForBill,
 * scaled to what is left on each). Paid is the ore in accepted contracts that
 * were credited as tax. A contract is shown against the bill it was made for
 * (its tax code, or the bill it was first applied to); when it paid more than
 * that bill, the rest went to the pilot's other bills or balance.
 */
class OreTally
{
    /** @var array<int, bool> character id => pays in ore, for this request */
    private array $form = [];

    public function __construct(private OreTaxService $ore)
    {
    }

    /**
     * Whether a bill's pilot pays in ore.
     */
    public function paysInOre(MiningTax $bill): bool
    {
        $characterId = (int) $bill->character_id;

        return $this->form[$characterId] ??= $this->ore->formForCharacter($characterId) === OreTaxService::FORM_ORE;
    }

    /**
     * Ore still to hand in on one open bill.
     *
     * @return array<int, array{type_id:int,name:string,units:int,volume:float,isk:float}>
     */
    public function owedOn(MiningTax $bill): array
    {
        if (!in_array($bill->status, MiningTax::OUTSTANDING_STATUSES, true)) {
            return [];
        }

        try {
            $lines = $this->ore->linesForBill($bill);
        } catch (\Throwable $e) {
            Log::warning('Mining Manager: could not work out the ore for a bill: ' . $e->getMessage(), ['tax_id' => $bill->id]);

            return [];
        }

        $out = [];
        foreach ($lines as $typeId => $line) {
            $out[(int) $typeId] = [
                'type_id' => (int) $typeId,
                'name' => (string) ($line['name'] ?? ('Type ' . $typeId)),
                'units' => (int) ($line['hand_in'] ?? 0),
                'volume' => (float) ($line['volume'] ?? 0),
                'isk' => (float) ($line['isk'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Ore owed on several bills, added up by ore.
     *
     * @param iterable<MiningTax> $bills
     */
    public function owed(iterable $bills): array
    {
        $total = [];
        foreach ($bills as $bill) {
            $total = $this->add($total, $this->owedOn($bill));
        }

        return $this->sorted($total);
    }

    /**
     * Ore paid in credited contracts, per bill.
     *
     * @param int[] $billIds
     * @return array<int, array<int, array{type_id:int,name:string,units:int,isk:float}>> bill id => ore
     */
    public function paidByBill(array $billIds): array
    {
        if (!$billIds) {
            return [];
        }

        $out = [];
        OreContract::whereIn('mining_tax_id', $billIds)
            ->where('status', OreContract::CREDITED)
            ->get(['contract_id', 'mining_tax_id', 'items'])
            ->each(function ($contract) use (&$out) {
                $id = (int) $contract->mining_tax_id;
                $out[$id] = $this->add($out[$id] ?? [], $this->itemsOf($contract));
            });

        return array_map(fn ($lines) => $this->sorted($lines), $out);
    }

    /**
     * Ore a set of pilots has paid in credited contracts, ever.
     *
     * @param int[] $characterIds
     * @return array{lines: array, contracts: int, isk: float}
     */
    public function paidBy(array $characterIds): array
    {
        $lines = [];
        $count = 0;
        $isk = 0.0;

        OreContract::whereIn('issuer_id', $characterIds ?: [0])
            ->where('status', OreContract::CREDITED)
            ->get(['contract_id', 'items', 'value'])
            ->each(function ($contract) use (&$lines, &$count, &$isk) {
                $lines = $this->add($lines, $this->itemsOf($contract));
                $count++;
                $isk += (float) $contract->value;
            });

        return ['lines' => $this->sorted($lines), 'contracts' => $count, 'isk' => $isk];
    }

    /**
     * One contract's ore, by type.
     */
    private function itemsOf(OreContract $contract): array
    {
        $out = [];
        foreach ($contract->items ?? [] as $item) {
            $typeId = (int) ($item['type_id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $out[$typeId] = [
                'type_id' => $typeId,
                'name' => (string) ($item['name'] ?? ('Type ' . $typeId)),
                'units' => (int) ($item['units'] ?? 0),
                'isk' => (float) ($item['value'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Add two by-ore lists together.
     */
    private function add(array $into, array $lines): array
    {
        foreach ($lines as $typeId => $line) {
            if (!isset($into[$typeId])) {
                $into[$typeId] = $line;
                continue;
            }
            foreach (['units', 'volume', 'isk'] as $field) {
                if (isset($line[$field])) {
                    $into[$typeId][$field] = ($into[$typeId][$field] ?? 0) + $line[$field];
                }
            }
        }

        return $into;
    }

    /**
     * Most units first.
     */
    private function sorted(array $lines): array
    {
        uasort($lines, fn ($a, $b) => ($b['units'] <=> $a['units']) ?: strcmp($a['name'], $b['name']));

        return $lines;
    }
}
