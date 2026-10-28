<?php

namespace MiningManager\Services\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MiningManager\Models\MiningLedger;
use MiningManager\Models\MoonExtraction;
use MiningManager\Models\MoonExtractionHistory;
use MiningManager\Services\Character\CharacterNames;
use MiningManager\Services\Configuration\SettingsManagerService;
use MiningManager\Services\Moon\MoonOreHelper;
use MiningManager\Services\Moon\RefineryService;

/**
 * Everything the Mining Metrics page shows for one month: the corporation's
 * moon mining, laid out like eve-ro.com/tools/mining.
 *
 * Scope: mining recorded by the Moon Owner Corporation's refineries (moon
 * observer rows in the ledger, whose corporation_id is the structure owner),
 * members and visiting miners alike. Values are the ledger's: ISK at the day's
 * prices, and the tax worked out at the configured rates.
 *
 * The result is cached briefly (five minutes for the current month, an hour
 * for a closed one), since several of its queries read a month of the ledger.
 */
class MiningMetricsService
{
    /** Base ore names per tier, as the corporation page lists them. */
    public const TIER_ORES = [
        'R4' => ['Bitumens', 'Zeolites', 'Sylvite', 'Coesite'],
        'R8' => ['Titanite', 'Euxenite', 'Cobaltite', 'Scheelite'],
        'R16' => ['Otavite', 'Sperrylite', 'Chromite', 'Vanadinite'],
        'R32' => ['Pollucite', 'Zircon', 'Carnotite', 'Cinnabar'],
        'R64' => ['Xenotime', 'Monazite', 'Loparite', 'Ytterbite'],
    ];

    public const TREND_CHOICES = [12, 24, 36];

    public function __construct(
        private SettingsManagerService $settings,
        private RefineryService $refineries,
        private CharacterNames $names
    ) {
    }

    public function moonOwnerCorporationId(): ?int
    {
        $id = $this->settings->getTaxProgramCorporationId();

        return $id ? (int) $id : null;
    }

    public function forMonth(Carbon $month, int $trendMonths = 12): array
    {
        $corporationId = $this->moonOwnerCorporationId();
        $month = $month->copy()->startOfMonth();
        $trendMonths = in_array($trendMonths, self::TREND_CHOICES, true) ? $trendMonths : 12;

        if (!$corporationId) {
            return ['configured' => false, 'month' => $month];
        }

        $isCurrent = $month->isSameMonth(Carbon::now());
        $key = "mining-manager:metrics:v2:{$corporationId}:{$month->format('Y-m')}:{$trendMonths}";

        $data = Cache::remember($key, $isCurrent ? 300 : 3600, function () use ($corporationId, $month, $trendMonths) {
            return $this->build($corporationId, $month, $trendMonths);
        });

        $data['month'] = $month;

        return $data;
    }

    private function build(int $corporationId, Carbon $month, int $trendMonths): array
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $refineries = $this->refineryCards($corporationId, $month);
        $byStructure = $this->sumsBy($corporationId, $start, $end, 'l.observer_id');
        foreach ($refineries['cards'] as &$card) {
            $sums = $byStructure[$card['structure_id']] ?? [];
            $card['isk'] = (float) ($sums['isk'] ?? 0);
            $card['tax'] = (float) ($sums['tax'] ?? 0);
            $card['volume'] = (float) ($sums['volume'] ?? 0);
            $card['active'] = $card['volume'] > 0 || $card['isk'] > 0;
        }
        unset($card);

        $players = $this->players($corporationId, $start, $end);
        $daily = MetricsMath::series(
            MetricsMath::daysOf($month),
            $this->sumsBy($corporationId, $start, $end, 'l.date')
        );

        $trendKeys = MetricsMath::monthsEndingWith($month, $trendMonths);
        $trendStart = Carbon::createFromFormat('Y-m-d', $trendKeys[0] . '-01')->toDateString();
        $trend = MetricsMath::series(
            $trendKeys,
            $this->sumsBy($corporationId, $trendStart, $end, "DATE_FORMAT(l.date, '%Y-%m')")
        );

        $totals = ['isk' => 0.0, 'tax' => 0.0, 'volume' => 0.0];
        foreach ($players as $player) {
            $totals['isk'] += $player['isk'];
            $totals['tax'] += $player['tax'];
            $totals['volume'] += $player['volume'];
        }
        $totals['members'] = count(array_filter($players, fn ($p) => !$p['guest']));
        $totals['guests'] = count($players) - $totals['members'];

        return [
            'configured' => true,
            'corporation' => $this->corporation($corporationId),
            'tiers' => $this->tiers($corporationId),
            'totals' => $totals,
            'refineries' => $refineries['counts'],
            'systems' => $refineries['systems'],
            'structures' => $refineries['cards'],
            'players' => $players,
            'daily' => $daily,
            'trend' => $trend,
            'trendMonths' => $trendMonths,
            'periods' => $this->periods($corporationId),
        ];
    }

    /**
     * Ledger sums for the corporation's refineries, grouped by one column.
     *
     * @return array<string, array{isk:float,tax:float,volume:float,quantity:float}>
     */
    private function sumsBy(int $corporationId, string $from, string $to, string $groupExpr): array
    {
        $rows = DB::table('mining_ledger as l')
            ->leftJoin('invTypes as t', 't.typeID', '=', 'l.type_id')
            ->where('l.corporation_id', $corporationId)
            ->whereNotNull('l.observer_id')
            ->whereBetween('l.date', [$from, $to])
            ->selectRaw("{$groupExpr} as k,
                SUM(l.total_value) as isk,
                SUM(l.tax_amount) as tax,
                SUM(l.quantity * COALESCE(t.volume, 0)) as volume,
                SUM(l.quantity) as quantity")
            ->groupBy(DB::raw($groupExpr))
            ->get();

        $out = [];
        foreach ($rows as $row) {
            // Dates come back with a time on some drivers and are cut to the
            // day; anything else (structure and character IDs) is kept whole.
            // Cutting every key to 10 characters truncated the 13-digit
            // structure IDs, so no refinery ever matched its mining.
            $key = MetricsMath::groupKey($row->k);
            // Added up, not set: rows grouped by a date with a time in it
            // land on the same day.
            $out[$key] = [
                'isk' => ($out[$key]['isk'] ?? 0.0) + (float) $row->isk,
                'tax' => ($out[$key]['tax'] ?? 0.0) + (float) $row->tax,
                'volume' => ($out[$key]['volume'] ?? 0.0) + (float) $row->volume,
                'quantity' => ($out[$key]['quantity'] ?? 0.0) + (float) $row->quantity,
            ];
        }

        return $out;
    }

    private function players(int $corporationId, string $from, string $to): array
    {
        $byCharacter = $this->sumsBy($corporationId, $from, $to, 'l.character_id');
        if (!$byCharacter) {
            return [];
        }

        $characterIds = array_map('intval', array_keys($byCharacter));
        $mainOf = $this->mainsFor($characterIds);

        $nameIds = array_values(array_unique(array_merge($characterIds, array_values($mainOf))));
        $names = [];
        foreach ($this->names->many($nameIds) as $id => $info) {
            $names[(int) $id] = $info['name'] ?? (string) $id;
        }

        $players = MetricsMath::players(
            array_combine($characterIds, array_values($byCharacter)),
            $mainOf,
            $names
        );

        foreach ($players as &$player) {
            $player['portrait'] = 'https://images.evetech.net/characters/' . $player['character_id'] . '/portrait?size=128';
        }

        return $players;
    }

    /**
     * Main character of each registered character (its SeAT user's main).
     *
     * @return array<int, int>
     */
    private function mainsFor(array $characterIds): array
    {
        $mainOf = [];

        foreach (array_chunk($characterIds, 1000) as $chunk) {
            $rows = DB::table('refresh_tokens as rt')
                ->join('users as u', 'u.id', '=', 'rt.user_id')
                ->whereIn('rt.character_id', $chunk)
                ->get(['rt.character_id', 'u.main_character_id']);

            foreach ($rows as $row) {
                $mainOf[(int) $row->character_id] = (int) ($row->main_character_id ?: $row->character_id);
            }
        }

        return $mainOf;
    }

    /**
     * Refinery cards plus the counts and systems the ring and list show.
     */
    private function refineryCards(int $corporationId, Carbon $month): array
    {
        $refineries = $this->refineries->refineriesForCorporation($corporationId);
        $ids = $refineries->pluck('structure_id')->map(fn ($id) => (int) $id)->all();

        if (!$ids) {
            return ['cards' => [], 'counts' => MetricsMath::refineryCounts([]), 'systems' => []];
        }

        $universe = DB::table('universe_structures')
            ->whereIn('structure_id', $ids)
            ->get(['structure_id', 'name', 'solar_system_id'])
            ->keyBy('structure_id');

        $systemIds = [];
        foreach ($refineries as $refinery) {
            $systemIds[(int) $refinery->structure_id] = (int) ($refinery->system_id
                ?? optional($universe->get($refinery->structure_id))->solar_system_id
                ?? 0);
        }
        $systemNames = DB::table('solar_systems')
            ->whereIn('system_id', array_values(array_filter($systemIds)))
            ->pluck('name', 'system_id');

        // Latest extraction per refinery, for its status now and its moon's ores.
        $latest = MoonExtraction::whereIn('structure_id', $ids)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('chunk_arrival_time')
            ->get()
            ->unique('structure_id')
            ->keyBy('structure_id');

        $history = MoonExtractionHistory::whereIn('structure_id', $ids)
            ->whereNotNull('ore_composition')
            ->orderByDesc('chunk_arrival_time')
            ->get(['structure_id', 'ore_composition'])
            ->unique('structure_id')
            ->keyBy('structure_id');

        // The chunk each refinery delivered in this month, if any.
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $arrivals = MoonExtraction::whereIn('structure_id', $ids)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('chunk_arrival_time', [$monthStart, $monthEnd])
            ->orderByDesc('chunk_arrival_time')
            ->get(['structure_id', 'chunk_arrival_time'])
            ->unique('structure_id')
            ->keyBy('structure_id');
        $archivedArrivals = MoonExtractionHistory::whereIn('structure_id', $ids)
            ->whereBetween('chunk_arrival_time', [$monthStart, $monthEnd])
            ->orderByDesc('chunk_arrival_time')
            ->get(['structure_id', 'chunk_arrival_time'])
            ->unique('structure_id')
            ->keyBy('structure_id');

        $cards = [];
        $statuses = [];
        $systems = [];

        foreach ($ids as $id) {
            $extraction = $latest->get($id);
            $status = 'idle';
            if ($extraction) {
                try {
                    $status = $extraction->getEffectiveStatus();
                } catch (\Throwable $e) {
                    $status = (string) $extraction->status;
                }
            }
            $statuses[] = $status;

            $composition = $extraction && is_array($extraction->ore_composition) && $extraction->ore_composition
                ? $extraction->ore_composition
                : (optional($history->get($id))->ore_composition ?: null);

            $systemName = $systemNames[$systemIds[$id]] ?? null;
            $systems[$systemName ?? 'Unknown'] = ($systems[$systemName ?? 'Unknown'] ?? 0) + 1;

            $arrival = optional($arrivals->get($id))->chunk_arrival_time
                ?? optional($archivedArrivals->get($id))->chunk_arrival_time;

            $cards[] = [
                'structure_id' => $id,
                'type_id' => (int) optional($refineries->firstWhere('structure_id', $id))->type_id,
                'name' => optional($universe->get($id))->name ?? ($refineries->firstWhere('structure_id', $id)->name ?? "Structure {$id}"),
                'system' => $systemName,
                'rarity' => is_array($composition) ? MoonOreHelper::highestRarity($composition) : null,
                'status' => $status,
                'arrival' => $arrival ? Carbon::parse($arrival)->toDateString() : null,
            ];
        }

        usort($cards, fn ($a, $b) => [$a['system'] ?? 'zzz', $a['name']] <=> [$b['system'] ?? 'zzz', $b['name']]);
        ksort($systems, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'cards' => $cards,
            'counts' => MetricsMath::refineryCounts($statuses),
            'systems' => $systems,
        ];
    }

    /**
     * Each tier's tax rate for the Moon Owner Corporation, with its ores.
     */
    private function tiers(int $corporationId): array
    {
        $previous = $this->settings->getActiveCorporation();

        try {
            $this->settings->setActiveCorporation($corporationId);
            $rates = $this->settings->getTaxRates();
            $selector = $this->settings->getTaxSelector();
        } catch (\Throwable $e) {
            Log::warning('Mining Manager: could not read tax rates for the metrics page: ' . $e->getMessage());
            $rates = [];
            $selector = [];
        } finally {
            $this->settings->setActiveCorporation($previous);
        }

        $moonTaxed = empty($selector['no_moon_ore'])
            && (!empty($selector['all_moon_ore']) || !empty($selector['only_corp_moon_ore']) || $selector === []);

        $tiers = [];
        foreach (self::TIER_ORES as $tier => $ores) {
            $rate = $rates['moon_ore'][strtolower($tier)] ?? null;
            $tiers[] = [
                'tier' => $tier,
                'rate' => ($moonTaxed && $rate !== null && (float) $rate > 0) ? (float) $rate : null,
                'ores' => $ores,
            ];
        }

        return $tiers;
    }

    private function corporation(int $corporationId): array
    {
        $row = DB::table('corporation_infos')->where('corporation_id', $corporationId)->first(['name', 'ticker']);

        return [
            'id' => $corporationId,
            'name' => $row->name ?? "Corporation {$corporationId}",
            'ticker' => $row->ticker ?? null,
        ];
    }

    /**
     * Months with refinery mining, newest first, for the period picker:
     * [year => [month numbers]].
     */
    private function periods(int $corporationId): array
    {
        $months = DB::table('mining_ledger')
            ->where('corporation_id', $corporationId)
            ->whereNotNull('observer_id')
            ->selectRaw("DISTINCT DATE_FORMAT(date, '%Y-%m') as ym")
            ->orderByDesc('ym')
            ->pluck('ym')
            ->all();

        $now = Carbon::now()->format('Y-m');
        if (!in_array($now, $months, true)) {
            array_unshift($months, $now);
        }

        $out = [];
        foreach ($months as $ym) {
            [$year, $mon] = array_map('intval', explode('-', $ym));
            $out[$year][] = $mon;
        }

        foreach ($out as &$list) {
            sort($list);
        }
        unset($list);
        krsort($out);

        return $out;
    }
}
