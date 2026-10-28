<?php

namespace MiningManager\Services\Ledger;

use Illuminate\Support\Facades\DB;
use MiningManager\Support\MiningSource;

/**
 * Mining totals from the ledger for one source: Moon (the corporation's
 * refineries), Anomalies (everything else pilots mined) or All.
 *
 * The daily summaries hold one total per pilot and day with no split by
 * source, so anything that follows the switch is read from the ledger,
 * limited to the rows the summaries count (MiningSource::scope). Tax is not
 * here: it comes from the summaries and bills, whatever the source.
 */
class SourceTotals
{
    /**
     * @param int[] $characterIds
     */
    protected function base(array $characterIds, string $from, string $to, string $source)
    {
        // A bare end date takes in the whole of that day.
        if (strlen($to) === 10) {
            $to .= ' 23:59:59';
        }

        $query = DB::table('mining_ledger')
            ->whereIn('mining_ledger.character_id', $characterIds)
            ->whereBetween('mining_ledger.date', [$from, $to]);

        return MiningSource::scope($query, $source);
    }

    /**
     * @param int[] $characterIds
     * @return array{quantity:float,value:float,volume:float,active_days:int,best_day:float}
     */
    public function totals(array $characterIds, string $from, string $to, string $source): array
    {
        if (!$characterIds) {
            return ['quantity' => 0.0, 'value' => 0.0, 'volume' => 0.0, 'active_days' => 0, 'best_day' => 0.0];
        }

        $row = $this->base($characterIds, $from, $to, $source)
            ->leftJoin('invTypes', 'invTypes.typeID', '=', 'mining_ledger.type_id')
            ->selectRaw('
                COALESCE(SUM(mining_ledger.quantity), 0) as quantity,
                COALESCE(SUM(mining_ledger.total_value), 0) as value,
                COALESCE(SUM(mining_ledger.quantity * COALESCE(invTypes.volume, 0)), 0) as volume,
                COUNT(DISTINCT mining_ledger.date) as active_days
            ')
            ->first();

        $bestDay = (float) (collect($this->daily($characterIds, $from, $to, $source))->max() ?? 0);

        return [
            'quantity' => (float) ($row->quantity ?? 0),
            'value' => (float) ($row->value ?? 0),
            'volume' => (float) ($row->volume ?? 0),
            'active_days' => (int) ($row->active_days ?? 0),
            'best_day' => $bestDay,
        ];
    }

    /**
     * Moon and Anomalies side by side, for the split bar and columns.
     *
     * @param int[] $characterIds
     * @return array{moon:float,anomalies:float}
     */
    public function split(array $characterIds, string $from, string $to): array
    {
        if (!$characterIds) {
            return ['moon' => 0.0, 'anomalies' => 0.0];
        }

        $row = $this->base($characterIds, $from, $to, MiningSource::ALL)
            ->selectRaw(
                MiningSource::sumSql(MiningSource::MOON, 'mining_ledger.total_value', 'mining_ledger.observer_id') . ' as moon, '
                . MiningSource::sumSql(MiningSource::ANOMALIES, 'mining_ledger.total_value', 'mining_ledger.observer_id') . ' as anomalies'
            )
            ->first();

        return [
            'moon' => (float) ($row->moon ?? 0),
            'anomalies' => (float) ($row->anomalies ?? 0),
        ];
    }

    /**
     * ISK per day.
     *
     * @param int[] $characterIds
     * @return array<string, float> Y-m-d => ISK
     */
    public function daily(array $characterIds, string $from, string $to, string $source): array
    {
        if (!$characterIds) {
            return [];
        }

        return $this->base($characterIds, $from, $to, $source)
            ->selectRaw('DATE(mining_ledger.date) as day, SUM(mining_ledger.total_value) as value')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('value', 'day')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    /**
     * Ores by ISK, most first.
     *
     * @param int[] $characterIds
     * @return array<int, array{type_id:int,name:string,value:float}>
     */
    public function topOres(array $characterIds, string $from, string $to, string $source, int $limit = 10): array
    {
        if (!$characterIds) {
            return [];
        }

        return $this->base($characterIds, $from, $to, $source)
            ->leftJoin('invTypes', 'invTypes.typeID', '=', 'mining_ledger.type_id')
            ->selectRaw('mining_ledger.type_id, MAX(invTypes.typeName) as name, SUM(mining_ledger.total_value) as value')
            ->groupBy('mining_ledger.type_id')
            ->orderByDesc('value')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'type_id' => (int) $row->type_id,
                'name' => (string) ($row->name ?? ('Type ' . $row->type_id)),
                'value' => (float) $row->value,
            ])
            ->all();
    }

    /**
     * Per pilot: ISK, units, m³ and days mined, plus the Moon and Anomalies
     * parts of the ISK.
     *
     * @param int[] $characterIds
     * @return array<int, array{value:float,quantity:float,volume:float,days:int,moon:float,anomalies:float,moon_ore_value:float,ice_value:float,gas_value:float,abyssal_ore_value:float,triglavian_ore_value:float,regular_ore_value:float}>
     */
    public function byCharacter(array $characterIds, string $from, string $to, string $source): array
    {
        if (!$characterIds) {
            return [];
        }

        $rows = $this->base($characterIds, $from, $to, $source)
            ->leftJoin('invTypes', 'invTypes.typeID', '=', 'mining_ledger.type_id')
            ->selectRaw('
                mining_ledger.character_id,
                SUM(mining_ledger.total_value) as value,
                SUM(mining_ledger.quantity) as quantity,
                SUM(mining_ledger.quantity * COALESCE(invTypes.volume, 0)) as volume,
                COUNT(DISTINCT mining_ledger.date) as days, '
                . MiningSource::sumSql(MiningSource::MOON, 'mining_ledger.total_value', 'mining_ledger.observer_id') . ' as moon, '
                . MiningSource::sumSql(MiningSource::ANOMALIES, 'mining_ledger.total_value', 'mining_ledger.observer_id') . ' as anomalies,
                SUM(CASE WHEN mining_ledger.is_moon_ore = 1 THEN mining_ledger.total_value ELSE 0 END) as moon_ore_value,
                SUM(CASE WHEN mining_ledger.is_ice = 1 THEN mining_ledger.total_value ELSE 0 END) as ice_value,
                SUM(CASE WHEN mining_ledger.is_gas = 1 THEN mining_ledger.total_value ELSE 0 END) as gas_value,
                SUM(CASE WHEN mining_ledger.is_abyssal = 1 THEN mining_ledger.total_value ELSE 0 END) as abyssal_ore_value,
                SUM(CASE WHEN mining_ledger.is_triglavian = 1 THEN mining_ledger.total_value ELSE 0 END) as triglavian_ore_value,
                SUM(CASE WHEN mining_ledger.is_moon_ore = 0 AND mining_ledger.is_ice = 0 AND mining_ledger.is_gas = 0
                    AND mining_ledger.is_abyssal = 0 AND mining_ledger.is_triglavian = 0 THEN mining_ledger.total_value ELSE 0 END) as regular_ore_value'
            )
            ->groupBy('mining_ledger.character_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->character_id] = [
                'value' => (float) $row->value,
                'quantity' => (float) $row->quantity,
                'volume' => (float) $row->volume,
                'days' => (int) $row->days,
                'moon' => (float) $row->moon,
                'anomalies' => (float) $row->anomalies,
                'moon_ore_value' => (float) $row->moon_ore_value,
                'ice_value' => (float) $row->ice_value,
                'gas_value' => (float) $row->gas_value,
                'abyssal_ore_value' => (float) $row->abyssal_ore_value,
                'triglavian_ore_value' => (float) $row->triglavian_ore_value,
                'regular_ore_value' => (float) $row->regular_ore_value,
            ];
        }

        return $out;
    }

    /**
     * ISK per calendar month.
     *
     * @param int[] $characterIds
     * @return array<string, float> Y-m => ISK
     */
    public function monthly(array $characterIds, string $from, string $to, string $source): array
    {
        if (!$characterIds) {
            return [];
        }

        return $this->base($characterIds, $from, $to, $source)
            ->selectRaw("DATE_FORMAT(mining_ledger.date, '%Y-%m') as month_key, SUM(mining_ledger.total_value) as value")
            ->groupBy('month_key')
            ->pluck('value', 'month_key')
            ->map(fn ($value) => (float) $value)
            ->all();
    }
}
