<?php

namespace MiningManager\Services\Tax\Ore;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MiningManager\Models\MiningLedgerDailySummary;
use MiningManager\Models\MiningTax;
use MiningManager\Services\Configuration\SettingsManagerService;

/**
 * Paying mining tax in ore instead of ISK.
 *
 * Some corporations take their tax as ISK sent to the corporation wallet;
 * others take a share of the ore itself, handed over in an item exchange
 * contract to the corporation. This is set per corporation (Settings > Tax
 * Rates > How members pay). Bills stay ISK amounts either way, which keeps
 * reminders, balances and reports working as they do; in ore mode members
 * are shown the ore their bill comes to, and accepted contracts are credited
 * against it (OreContractService).
 */
class OreTaxService
{
    public const FORM_KEY = 'tax_rates.payment_form';
    public const SINCE_KEY = 'tax_rates.ore_contracts_since';

    public const FORM_ISK = 'isk';
    public const FORM_ORE = 'ore';

    public function __construct(private SettingsManagerService $settings)
    {
    }

    /** How a corporation's members pay: 'isk' or 'ore'. */
    public function formForCorporation(?int $corporationId): string
    {
        $form = $this->settings->getSettingForCorporation(self::FORM_KEY, $corporationId, self::FORM_ISK);

        return $form === self::FORM_ORE ? self::FORM_ORE : self::FORM_ISK;
    }

    /**
     * How a character pays: their corporation's choice, or the Moon Owner
     * Corporation's for someone whose corporation is not configured.
     */
    public function formForCharacter(?int $characterId): string
    {
        return $this->formForCorporation($this->corporationFor($characterId));
    }

    /**
     * The corporation whose choice applies to a character, and which their
     * ore contracts go to: their own corporation when it has its own
     * setting, otherwise the Moon Owner Corporation.
     */
    public function corporationFor(?int $characterId): ?int
    {
        $corporationId = $characterId ? $this->corporationOf($characterId) : null;

        if ($corporationId && $this->hasOwnSetting($corporationId)) {
            return $corporationId;
        }

        return $this->settings->getTaxProgramCorporationId();
    }

    public function corporationName(?int $corporationId): ?string
    {
        return $corporationId
            ? DB::table('corporation_infos')->where('corporation_id', $corporationId)->value('name')
            : null;
    }

    /** When contract checking started for a corporation (contracts before it are left alone). */
    public function contractsSince(int $corporationId): ?Carbon
    {
        $value = $this->settings->getSettingForCorporation(self::SINCE_KEY, $corporationId, null);

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Corporations that take tax in ore, with the date contract checking began.
     *
     * @return array<int, Carbon> corporation_id => since
     */
    public function oreCorporations(): array
    {
        $ids = DB::table('mining_manager_settings')
            ->where('key', self::FORM_KEY)
            ->where('value', self::FORM_ORE)
            ->pluck('corporation_id')
            ->all();

        $out = [];
        foreach ($ids as $id) {
            // A global "ore" choice applies to the Moon Owner Corporation.
            $corporationId = $id ?: $this->settings->getTaxProgramCorporationId();
            if ($corporationId && $this->formForCorporation((int) $corporationId) === self::FORM_ORE) {
                $out[(int) $corporationId] = $this->contractsSince((int) $corporationId) ?? Carbon::now();
            }
        }

        return $out;
    }

    /**
     * The ore a bill comes to, scaled to what is still owed on it (or to the
     * whole bill with $remaining false).
     *
     * @return array<int, array{type_id:int, name:string, units:float, hand_in:int, isk:float, unit_value:float, volume:float}>
     */
    public function linesForBill(MiningTax $bill, bool $remaining = true): array
    {
        $target = $remaining ? $bill->getRemainingBalance() : (float) $bill->amount_owed;
        if ($target <= 0) {
            return [];
        }

        $key = sprintf('mining-manager:ore-lines:%d:%s:%s', $bill->id, $bill->updated_at?->timestamp ?? 0, $remaining ? 'r' : 'a');

        return Cache::remember($key, 600, function () use ($bill, $target) {
            [$start, $end] = $this->periodOf($bill);

            $ores = [];
            MiningLedgerDailySummary::whereIn('character_id', $this->accountCharacterIds((int) $bill->character_id))
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['ore_types'])
                ->each(function ($summary) use (&$ores) {
                    foreach ($summary->ore_types ?? [] as $ore) {
                        if (($ore['is_taxable'] ?? true) !== false) {
                            $ores[] = $ore;
                        }
                    }
                });

            $lines = OreMath::scaleTo(OreMath::linesFromOres($ores), $target);
            $volumes = $this->volumes(array_keys($lines));

            foreach ($lines as $typeId => &$line) {
                $line['hand_in'] = OreMath::unitsToHandIn($line['units']);
                $line['volume'] = $line['hand_in'] * ($volumes[$typeId] ?? 0);
            }
            unset($line);

            return $lines;
        });
    }

    /**
     * ISK per unit of each ore on a player's open bills, for valuing ore they
     * hand in. A nominated bill's values come first; other bills fill in ores
     * it does not list.
     *
     * @return array<int, float> type_id => ISK per unit
     */
    public function unitValuesFor(int $characterId, ?int $primaryTaxId = null): array
    {
        $bills = MiningTax::whereIn('character_id', $this->accountCharacterIds($characterId))
            ->outstanding()
            ->orderByRaw('COALESCE(period_start, month) ASC')
            ->get();

        if ($primaryTaxId) {
            $bills = $bills->sortBy(fn ($bill) => $bill->id === $primaryTaxId ? 0 : 1)->values();
        }

        $values = [];
        foreach ($bills as $bill) {
            foreach ($this->linesForBill($bill, false) as $typeId => $line) {
                if (!isset($values[$typeId]) && $line['unit_value'] > 0) {
                    $values[$typeId] = $line['unit_value'];
                }
            }
        }

        return $values;
    }

    /**
     * The raw ore a compressed ore stands for, unit for unit (current EVE
     * compression keeps unit counts). Anything else maps to itself.
     *
     * @param int[] $typeIds
     * @return array<int, int> type_id => raw type_id
     */
    public function rawTypes(array $typeIds): array
    {
        $typeIds = array_values(array_unique(array_map('intval', $typeIds)));
        if (!$typeIds) {
            return [];
        }

        $names = DB::table('invTypes')->whereIn('typeID', $typeIds)->pluck('typeName', 'typeID')->all();
        $rawNames = [];
        foreach ($names as $id => $name) {
            if ($raw = OreMath::rawNameOf((string) $name)) {
                $rawNames[(int) $id] = $raw;
            }
        }

        $rawIds = $rawNames
            ? DB::table('invTypes')->whereIn('typeName', array_values(array_unique($rawNames)))->where('published', 1)->pluck('typeID', 'typeName')->all()
            : [];

        $out = [];
        foreach ($typeIds as $id) {
            $out[$id] = isset($rawNames[$id], $rawIds[$rawNames[$id]]) ? (int) $rawIds[$rawNames[$id]] : $id;
        }

        return $out;
    }

    /** @return array<int, string> type_id => name */
    public function typeNames(array $typeIds): array
    {
        return $typeIds
            ? DB::table('invTypes')->whereIn('typeID', $typeIds)->pluck('typeName', 'typeID')->map(fn ($n) => (string) $n)->all()
            : [];
    }

    /**
     * Every character on the SeAT account a character belongs to, the way
     * bills are made out (one bill per account, under its main).
     *
     * @return int[]
     */
    public function accountCharacterIds(int $characterId): array
    {
        $userId = DB::table('refresh_tokens')->where('character_id', $characterId)->value('user_id');

        if (!$userId) {
            $userId = DB::table('users')->where('main_character_id', $characterId)->value('id');
        }

        $ids = $userId
            ? DB::table('refresh_tokens')->where('user_id', $userId)->pluck('character_id')->map(fn ($id) => (int) $id)->all()
            : [];

        $ids[] = $characterId;

        return array_values(array_unique($ids));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function periodOf(MiningTax $bill): array
    {
        $start = Carbon::parse($bill->period_start ?? $bill->month)->startOfDay();
        $end = $bill->period_end ? Carbon::parse($bill->period_end)->endOfDay() : $start->copy()->endOfMonth();

        return [$start, $end];
    }

    private function corporationOf(int $characterId): ?int
    {
        $id = DB::table('character_affiliations')->where('character_id', $characterId)->value('corporation_id');

        return $id ? (int) $id : null;
    }

    private function hasOwnSetting(int $corporationId): bool
    {
        return DB::table('mining_manager_settings')
            ->where('key', self::FORM_KEY)
            ->where('corporation_id', $corporationId)
            ->exists();
    }

    /** @return array<int, float> type_id => m3 per unit */
    private function volumes(array $typeIds): array
    {
        return $typeIds
            ? DB::table('invTypes')->whereIn('typeID', $typeIds)->pluck('volume', 'typeID')->map(fn ($v) => (float) $v)->all()
            : [];
    }
}
