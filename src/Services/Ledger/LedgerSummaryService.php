<?php

namespace MiningManager\Services\Ledger;

use MiningManager\Models\MiningLedger;
use MiningManager\Models\MiningLedgerMonthlySummary;
use MiningManager\Models\MiningLedgerDailySummary;
use MiningManager\Services\Configuration\SettingsManagerService;
use MiningManager\Services\TypeIdRegistry;
use Seat\Eveapi\Models\Sde\SolarSystem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MiningManager\Services\OreClassifier;
use MiningManager\Support\MiningSource;

class LedgerSummaryService
{
    /**
     * Settings manager.
     *
     * @var SettingsManagerService
     */
    protected SettingsManagerService $settingsService;

    /**
     * Constructor.
     *
     * @param SettingsManagerService $settingsService
     */
    public function __construct(SettingsManagerService $settingsService)
    {
        $this->settingsService = $settingsService;
    }
    /**
     * Generate monthly summary for a specific character and month
     *
     * @param int $characterId
     * @param string $month YYYY-MM format
     * @return MiningLedgerMonthlySummary
     */
    public function generateMonthlySummary(int $characterId, string $month): MiningLedgerMonthlySummary
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        // Aggregate data from mining_ledger (only processed entries with calculated values)
        $summary = MiningLedger::where('character_id', $characterId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->whereNotNull('processed_at')
            ->selectRaw('
                character_id,
                MAX(corporation_id) as corporation_id,
                SUM(quantity) as total_quantity,
                SUM(total_value) as total_value,
                SUM(CASE WHEN is_moon_ore = 1 THEN total_value ELSE 0 END) as moon_ore_value,
                SUM(CASE WHEN is_ice = 1 THEN total_value ELSE 0 END) as ice_value,
                SUM(CASE WHEN is_gas = 1 THEN total_value ELSE 0 END) as gas_value,
                SUM(CASE WHEN is_abyssal = 1 THEN total_value ELSE 0 END) as abyssal_ore_value,
                SUM(CASE WHEN is_triglavian = 1 THEN total_value ELSE 0 END) as triglavian_ore_value,
                SUM(CASE WHEN is_moon_ore = 0 AND is_ice = 0 AND is_gas = 0 AND is_abyssal = 0 AND is_triglavian = 0 THEN total_value ELSE 0 END) as regular_ore_value
            ')
            ->groupBy('character_id')
            ->first();

        if (!$summary) {
            // No mining data for this character/month
            return MiningLedgerMonthlySummary::create([
                'character_id' => $characterId,
                'month' => $monthDate,
                'corporation_id' => null,
                'total_quantity' => 0,
                'total_value' => 0,
                'moon_ore_value' => 0,
                'regular_ore_value' => 0,
                'ice_value' => 0,
                'gas_value' => 0,
                'abyssal_ore_value' => 0,
                'triglavian_ore_value' => 0,
                'ore_breakdown' => [],
                'is_finalized' => true,
                'finalized_at' => now(),
            ]);
        }

        // Get ore breakdown (only processed entries)
        $oreBreakdown = MiningLedger::where('character_id', $characterId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->whereNotNull('processed_at')
            ->selectRaw('ore_type, type_id, SUM(quantity) as total_quantity, SUM(total_value) as total_value')
            ->groupBy('ore_type', 'type_id')
            ->get()
            ->toArray();

        // Create or update monthly summary
        return MiningLedgerMonthlySummary::updateOrCreate(
            [
                'character_id' => $characterId,
                'month' => $monthDate,
            ],
            [
                'corporation_id' => $summary->corporation_id,
                'total_quantity' => $summary->total_quantity,
                'total_value' => $summary->total_value,
                'moon_ore_value' => $summary->moon_ore_value,
                'regular_ore_value' => $summary->regular_ore_value,
                'ice_value' => $summary->ice_value,
                'gas_value' => $summary->gas_value,
                'abyssal_ore_value' => $summary->abyssal_ore_value,
                'triglavian_ore_value' => $summary->triglavian_ore_value,
                'ore_breakdown' => $oreBreakdown,
                'is_finalized' => true,
                'finalized_at' => now(),
            ]
        );
    }

    /**
     * Generate daily summary for a specific character and date.
     *
     * Builds the day's ore breakdown (what was mined and what it was worth at
     * the day's market price) and stores it in the ore_types JSON column.
     *
     * @param int $characterId
     * @param string $date YYYY-MM-DD format
     * @return MiningLedgerDailySummary
     */
    public function generateDailySummary(int $characterId, string $date): MiningLedgerDailySummary
    {
        // Rates are read in the corporation each entry belongs to, so the
        // settings context moves while a day is built. It goes back to the
        // caller's afterwards: Recalculate rebuilds a month of days and then
        // reads the exemption threshold and minimum for the moon owner, and a
        // context left on the last character's corporation answered instead.
        $callerContext = $this->settingsService->getActiveCorporation();

        try {
            return $this->buildDailySummary($characterId, $date);
        } finally {
            $this->settingsService->setActiveCorporation($callerContext);
        }
    }

    protected function buildDailySummary(int $characterId, string $date): MiningLedgerDailySummary
    {
        $dateCarbon = Carbon::parse($date);

        // Only aggregate entries that have been processed (have prices calculated).
        // Unprocessed entries have total_value = 0 and would produce zero-value summaries.
        $baseQuery = MiningLedger::where('character_id', $characterId)
            ->whereDate('date', $dateCarbon)
            ->whereNotNull('processed_at');

        $hasData = (clone $baseQuery)->exists();

        if (!$hasData) {
            // No processed mining data — create/update empty summary
            return MiningLedgerDailySummary::updateOrCreate(
                [
                    'character_id' => $characterId,
                    'date' => $dateCarbon,
                ],
                [
                    'corporation_id' => null,
                    'total_quantity' => 0,
                    'total_value' => 0,
                    'moon_ore_value' => 0,
                    'regular_ore_value' => 0,
                    'ice_value' => 0,
                    'gas_value' => 0,
                    'abyssal_ore_value' => 0,
                    'triglavian_ore_value' => 0,
                    'ore_types' => [],
                    'is_finalized' => !$dateCarbon->isSameMonth(now()),
                ]
            );
        }

        // Get the character's current corporation ID. The corporation_id
        // column was dropped from character_infos in SeAT 2019 — the current
        // affiliation lives in character_affiliations now. Reading it from
        // CharacterInfo directly would silently return null and break both
        // guest-mining detection.
        $characterCorpId = DB::table('character_affiliations')
            ->where('character_id', $characterId)
            ->value('corporation_id');
        $characterCorpId = $characterCorpId ? (int) $characterCorpId : null;

        // Moon Owner Corporation is a GLOBAL setting (not corp-context dependent).
        // Read it directly to avoid dependency on active corporation context.
        $moonOwnerCorpId = $this->settingsService->getSettingForCorporation(
            'general.moon_owner_corporation_id', null
        );

        // Configured corporations: a pilot's own mining log only counts while
        // they are in one of them.
        $homeCorporationIds = $this->settingsService->getHomeCorporationIds();

        // Get corporation_id for the summary — prefer Moon Owner Corp if entries exist
        $corporationId = (clone $baseQuery)
            ->whereNotNull('corporation_id')
            ->when($moonOwnerCorpId, function ($q) use ($moonOwnerCorpId) {
                return $q->where('corporation_id', $moonOwnerCorpId);
            })
            ->value('corporation_id');

        // Aggregate per ore type AND corporation for the day (only processed
        // entries), so other corporations' refineries can be left out below.
        $oreEntries = (clone $baseQuery)
            ->selectRaw('
                type_id,
                ore_type,
                corporation_id,
                SUM(quantity) as total_quantity,
                SUM(total_value) as total_value
            ')
            ->groupBy('type_id', 'ore_type', 'corporation_id')
            ->get();

        // Ore breakdown for the day
        $oreBreakdown = [];
        $totalQuantity = 0;
        $totalValue = 0;
        $moonOreValue = 0;
        $regularOreValue = 0;
        $iceValue = 0;
        $gasValue = 0;
        $abyssalOreValue = 0;
        $triglavianOreValue = 0;

        foreach ($oreEntries as $entry) {
            $typeId = $entry->type_id;
            $quantity = $entry->total_quantity;
            $value = $entry->total_value;
            $unitPrice = $quantity > 0 ? ($value / $quantity) : 0;

            // Determine ore category and name
            $oreName = $this->getOreName($typeId);
            $category = $this->getOreCategory($typeId);
            $moonRarity = TypeIdRegistry::isMoonOre($typeId)
                ? TypeIdRegistry::getMoonOreRarity($typeId)
                : null;

            $entryCorporationId = $entry->corporation_id;

            // ================================================================
            // Corporation filtering: only process relevant entries
            // ================================================================
            if ($entryCorporationId !== null) {
                // Observer data — ONLY process from Moon Owner Corp's structures
                if ((int) $entryCorporationId !== (int) ($moonOwnerCorpId ?? 0)) {
                    continue; // Skip entries from other corps' observers (e.g. Mount Othrys)
                }
            } else {
                // Character ledger entry (NULL corporation_id)
                // Only counted while the character is in a configured corporation
                if (!$characterCorpId || (
                    !empty($homeCorporationIds)
                    && !in_array((int) $characterCorpId, $homeCorporationIds, true)
                )) {
                    continue; // Character not in SeAT or not in any configured corp — skip
                }
            }

            $oreBreakdown[] = [
                'type_id' => $typeId,
                'ore_name' => $oreName,
                'category' => $category,
                'moon_rarity' => $moonRarity,
                'quantity' => (float) $quantity,
                'unit_price' => round($unitPrice, 2),
                'total_value' => round((float) $value, 2),
            ];

            // Accumulate totals
            $totalQuantity += $quantity;
            $totalValue += $value;

            // Category breakdown. Matches the specific-rarity output from
            // getOreCategory (moon_r4..moon_r64 instead of the old 'moon_ore').
            // Abyssal + Triglavian got dedicated columns in migration 000009 —
            // before that they were silently rolled into regular_ore_value
            // and invisible in dashboard charts.
            if (str_starts_with($category, 'moon_')) {
                $moonOreValue += $value;
            } elseif ($category === 'ice') {
                $iceValue += $value;
            } elseif ($category === 'gas') {
                $gasValue += $value;
            } elseif ($category === 'abyssal') {
                $abyssalOreValue += $value;
            } elseif ($category === 'triglavian') {
                $triglavianOreValue += $value;
            } else {
                // True regular ore — vanilla high/low/null sec ores
                $regularOreValue += $value;
            }
        }

        // Current month = not finalized (still in progress)
        $isFinalized = !$dateCarbon->isSameMonth(now());

        // Create or update daily summary
        return MiningLedgerDailySummary::updateOrCreate(
            [
                'character_id' => $characterId,
                'date' => $dateCarbon,
            ],
            [
                'corporation_id' => $corporationId,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'moon_ore_value' => $moonOreValue,
                'regular_ore_value' => $regularOreValue,
                'ice_value' => $iceValue,
                'gas_value' => $gasValue,
                'abyssal_ore_value' => $abyssalOreValue,
                'triglavian_ore_value' => $triglavianOreValue,
                'ore_types' => $oreBreakdown,
                'is_finalized' => $isFinalized,
            ]
        );
    }

    /**
     * Get ore name from invTypes table.
     *
     * @param int $typeId
     * @return string
     */
    private function getOreName(int $typeId): string
    {
        try {
            $name = DB::table('invTypes')
                ->where('typeID', $typeId)
                ->value('typeName');

            return $name ?: "Unknown Ore ({$typeId})";
        } catch (\Exception $e) {
            return "Type {$typeId}";
        }
    }

    /**
     * Get ore category string for a type ID.
     *
     * @param int $typeId
     * @return string
     */
    /**
     * Classify an ore type into the canonical category string.
     *
     * MUST match the values stored in mining_ledger.ore_category by
     * ProcessMiningLedgerCommand / ImportCharacterMiningCommand::classifyOreCategory,
     * AND the values enumerated in MiningEvent::EVENT_TYPE_ORE_CATEGORIES.
     *
     * Output format:
     *   moon_r4, moon_r8, moon_r16, moon_r32, moon_r64 — specific rarity
     *   ice
     *   gas
     *   abyssal
     *   triglavian
     *   ore (default — regular asteroid ore)
     */
    private function getOreCategory(int $typeId): string
    {
        return OreClassifier::category($typeId);
    }

    /**
     * Finalize all summaries for a given month
     * Run this on the 2nd of each month for the previous month
     *
     * @param string $month YYYY-MM format
     * @return array Statistics about finalization
     */
    public function finalizeMonth(string $month): array
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $startDate = $monthDate->copy()->startOfMonth();
        $endDate = $monthDate->copy()->endOfMonth();
        $stats = [
            'month' => $month,
            'monthly_summaries' => 0,
            'daily_summaries' => 0,
            'characters_processed' => 0,
            'errors' => [],
        ];

        try {
            // Get all unique characters who mined in this month
            $characters = MiningLedger::whereYear('date', $monthDate->year)
                ->whereMonth('date', $monthDate->month)
                ->distinct()
                ->pluck('character_id');

            $stats['characters_processed'] = $characters->count();

            // Pre-query all character+date pairs that actually have data
            // This avoids iterating every day of the month for every character
            $pairs = MiningLedger::whereBetween('date', [$startDate, $endDate])
                ->select('character_id', DB::raw('DATE(date) as mining_date'))
                ->distinct()
                ->get();

            // Group dates by character for efficient lookup
            $datesByCharacter = $pairs->groupBy('character_id');

            foreach ($characters as $characterId) {
                try {
                    // Generate monthly summary
                    $this->generateMonthlySummary($characterId, $month);
                    $stats['monthly_summaries']++;

                    // Generate daily summaries only for days with actual data
                    $characterDates = $datesByCharacter->get($characterId, collect());
                    foreach ($characterDates as $pair) {
                        $this->generateDailySummary($characterId, $pair->mining_date);
                        $stats['daily_summaries']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors'][] = "Character {$characterId}: " . $e->getMessage();
                    Log::error("Failed to finalize summaries for character {$characterId}", [
                        'month' => $month,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Finalized summaries for month {$month}", $stats);
        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Log::error("Failed to finalize month {$month}", [
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Get monthly summaries with fallback to live calculation
     *
     * @param string $month YYYY-MM format
     * @param int|null $corporationId
     * @return \Illuminate\Support\Collection
     */
    public function getMonthlySummaries(string $month, ?int $corporationId = null)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $isCurrentMonth = $monthDate->isSameMonth(now());

        // If it's the current month, always calculate live
        if ($isCurrentMonth) {
            return $this->calculateLiveMonthlySummaries($month, $corporationId);
        }

        // For past months, try to get finalized summaries first
        $summaries = MiningLedgerMonthlySummary::forMonth($monthDate)
            ->forCorporation($corporationId)
            ->finalized()
            ->get();

        // If no finalized summaries exist, calculate live
        if ($summaries->isEmpty()) {
            return $this->calculateLiveMonthlySummaries($month, $corporationId);
        }

        return $summaries;
    }

    /**
     * Calculate live monthly summaries from raw ledger data
     *
     * @param string $month YYYY-MM format
     * @param int|null $corporationId
     * @return \Illuminate\Support\Collection
     */
    protected function calculateLiveMonthlySummaries(string $month, ?int $corporationId = null)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        $query = MiningLedger::whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month);

        if ($corporationId) {
            $query->where('corporation_id', $corporationId);
        }

        // Group by character_id only — a character's total should be aggregated
        // across all corporations they mined at. Use MAX(corporation_id) to pick
        // the primary corporation for display purposes.
        $summaries = $query->selectRaw('
                character_id,
                MAX(corporation_id) as corporation_id,
                SUM(quantity) as total_quantity,
                SUM(total_value) as total_value,
                SUM(CASE WHEN is_moon_ore = 1 THEN total_value ELSE 0 END) as moon_ore_value,
                SUM(CASE WHEN is_ice = 1 THEN total_value ELSE 0 END) as ice_value,
                SUM(CASE WHEN is_gas = 1 THEN total_value ELSE 0 END) as gas_value,
                SUM(CASE WHEN is_abyssal = 1 THEN total_value ELSE 0 END) as abyssal_ore_value,
                SUM(CASE WHEN is_triglavian = 1 THEN total_value ELSE 0 END) as triglavian_ore_value,
                SUM(CASE WHEN is_moon_ore = 0 AND is_ice = 0 AND is_gas = 0 AND is_abyssal = 0 AND is_triglavian = 0 THEN total_value ELSE 0 END) as regular_ore_value
            ')
            ->groupBy('character_id')
            ->get();

        return $summaries;
    }

    /**
     * Get daily summaries for a character in a specific month.
     *
     * For the current month, reads stored summaries (which contain rich
     * ore breakdown from the daily update command).
     * Falls back to live SQL aggregation only if no summaries exist yet.
     *
     * @param int $characterId
     * @param string $month YYYY-MM format
     * @return \Illuminate\Support\Collection
     */
    public function getDailySummaries(int $characterId, string $month)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();
        $isCurrentMonth = $monthDate->isSameMonth(now());

        if (!$isCurrentMonth) {
            // Past months: prefer finalized summaries
            $summaries = MiningLedgerDailySummary::forCharacter($characterId)
                ->forMonth($monthDate)
                ->finalized()
                ->orderBy('date')
                ->get();

            if ($summaries->isNotEmpty()) {
                return $summaries;
            }
        } else {
            // Current month: use stored summaries (may be non-finalized)
            // These contain the rich ore_types JSON
            $summaries = MiningLedgerDailySummary::forCharacter($characterId)
                ->forMonth($monthDate)
                ->orderBy('date')
                ->get();

            if ($summaries->isNotEmpty()) {
                return $summaries;
            }
        }

        // Fallback: no stored summaries yet, calculate live from raw ledger
        return $this->calculateLiveDailySummaries($characterId, $month);
    }

    /**
     * Calculate live daily summaries from raw ledger data
     *
     * @param int $characterId
     * @param string $month YYYY-MM format
     * @return \Illuminate\Support\Collection
     */
    protected function calculateLiveDailySummaries(int $characterId, string $month)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        $summaries = MiningLedger::where('character_id', $characterId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->selectRaw('
                DATE(date) as date,
                character_id,
                corporation_id,
                SUM(quantity) as total_quantity,
                SUM(total_value) as total_value,
                SUM(CASE WHEN is_moon_ore = 1 THEN total_value ELSE 0 END) as moon_ore_value,
                SUM(CASE WHEN is_ice = 1 THEN total_value ELSE 0 END) as ice_value,
                SUM(CASE WHEN is_gas = 1 THEN total_value ELSE 0 END) as gas_value,
                SUM(CASE WHEN is_abyssal = 1 THEN total_value ELSE 0 END) as abyssal_ore_value,
                SUM(CASE WHEN is_triglavian = 1 THEN total_value ELSE 0 END) as triglavian_ore_value,
                SUM(CASE WHEN is_moon_ore = 0 AND is_ice = 0 AND is_gas = 0 AND is_abyssal = 0 AND is_triglavian = 0 THEN total_value ELSE 0 END) as regular_ore_value
            ')
            ->groupBy(DB::raw('DATE(date)'), 'character_id', 'corporation_id')
            ->orderBy('date')
            ->get();

        return $summaries;
    }

    /**
     * Get enhanced monthly summaries with ore types and systems
     *
     * PERFORMANCE: Uses batch queries instead of per-character N+1 queries.
     * Previously ran 2+ queries per character (200+ queries for 50 miners).
     * Now uses 2 batch queries + 1 system name lookup regardless of miner count.
     *
     * @param string $month YYYY-MM format
     * @param int|null $corporationId
     * @param string|null $source MiningSource to read what was mined for; null keeps the summaries' own totals
     * @return \Illuminate\Support\Collection
     */
    public function getEnhancedMonthlySummaries(string $month, ?int $corporationId = null, ?string $source = null)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        // Get base summaries
        $summaries = $this->getMonthlySummaries($month, $corporationId);

        if ($summaries->isEmpty()) {
            return $summaries;
        }

        // Collect all character IDs once
        $characterIds = $summaries->pluck('character_id')->unique()->toArray();

        // With a source picked, what was mined is read from the ledger for that
        // source (Moon, Anomalies or both), split into Moon and Anomalies.
        if ($source !== null) {
            $byCharacter = app(SourceTotals::class)->byCharacter(
                array_map('intval', $characterIds),
                $monthDate->format('Y-m-d'),
                $monthDate->copy()->endOfMonth()->format('Y-m-d'),
                $source
            );

            $summaries = $summaries->map(function ($summary) use ($byCharacter) {
                $row = $byCharacter[(int) $summary->character_id] ?? null;

                $summary->total_value = $row['value'] ?? 0.0;
                $summary->total_quantity = $row['quantity'] ?? 0.0;
                $summary->moon_value = $row['moon'] ?? 0.0;
                $summary->anomalies_value = $row['anomalies'] ?? 0.0;
                foreach (['moon_ore_value', 'ice_value', 'gas_value', 'abyssal_ore_value', 'triglavian_ore_value', 'regular_ore_value'] as $field) {
                    $summary->{$field} = $row[$field] ?? 0.0;
                }

                return $summary;
            });

            if ($source !== MiningSource::ALL) {
                $summaries = $summaries->filter(fn ($summary) => $summary->total_value > 0)->values();

                if ($summaries->isEmpty()) {
                    return $summaries;
                }

                $characterIds = $summaries->pluck('character_id')->unique()->toArray();
            }
        }

        // The ore, volume and system lookups below follow the same source.
        $scoped = function ($query) use ($source) {
            return $source !== null ? MiningSource::scope($query, $source) : $query;
        };

        // --- BATCH QUERY 1: Get all ore type_ids for all characters in one query ---
        $allOreTypes = $scoped(MiningLedger::whereIn('character_id', $characterIds)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month))
            ->select('character_id', 'type_id')
            ->distinct()
            ->get()
            ->groupBy('character_id')
            ->map(fn($group) => $group->pluck('type_id')->toArray());

        // --- BATCH QUERY 2: Get total volume (m³) for all characters in one query ---
        $allVolumes = $scoped(MiningLedger::whereIn('character_id', $characterIds)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month))
            ->join('invTypes', 'mining_ledger.type_id', '=', 'invTypes.typeID')
            ->selectRaw('character_id, SUM(mining_ledger.quantity * invTypes.volume) as total_volume_m3')
            ->groupBy('character_id')
            ->pluck('total_volume_m3', 'character_id');

        // --- BATCH QUERY 3: Get all system data for all characters in one query ---
        $allSystemData = $scoped(MiningLedger::whereIn('character_id', $characterIds)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month))
            ->select('character_id', 'solar_system_id', DB::raw('SUM(total_value) as system_value'))
            ->groupBy('character_id', 'solar_system_id')
            ->get()
            ->groupBy('character_id');

        // --- BATCH QUERY 4: Load all unique solar system names in one query ---
        $allSystemIds = $allSystemData->flatten()->pluck('solar_system_id')->unique()->filter()->toArray();
        $solarSystems = [];
        if (!empty($allSystemIds)) {
            try {
                $pk = \MiningManager\Models\MiningLedger::getSolarSystemPrimaryKey();
                $solarSystems = SolarSystem::whereIn($pk, $allSystemIds)->get()->keyBy($pk);
            } catch (\Exception $e) {
                Log::debug('LedgerSummaryService: Failed to batch load solar systems', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Enrich each summary from the pre-loaded batch data (no extra queries)
        $summaries = $summaries->map(function ($summary) use ($allOreTypes, $allVolumes, $allSystemData, $solarSystems) {
            $charId = $summary->character_id;

            // Ore types from batch
            $summary->ore_type_ids = $allOreTypes->get($charId, []);

            // Volume from batch
            $summary->total_volume_m3 = (float) $allVolumes->get($charId, 0);

            // System data from batch with pre-loaded names
            $systemData = $allSystemData->get($charId, collect())->sortByDesc('system_value')->values();

            $systems = $systemData->map(function ($item) use ($solarSystems) {
                $item->solarSystem = $solarSystems->get($item->solar_system_id);
                return $item;
            });

            $summary->systems = $systems;
            $summary->primary_system = $systems->first();
            $summary->system_count = $systems->count();

            return $summary;
        });

        return $summaries;
    }

    /**
     * Get character mining details in a specific system
     *
     * @param int $characterId
     * @param string $month YYYY-MM format
     * @param int $systemId
     * @return \Illuminate\Support\Collection
     */
    public function getCharacterSystemDetails(int $characterId, string $month, int $systemId)
    {
        $monthDate = Carbon::parse($month)->startOfMonth();

        return MiningLedger::with(['type', 'solarSystem'])
            ->where('character_id', $characterId)
            ->where('solar_system_id', $systemId)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Group character summaries by main character
     * Uses SeAT v5 structure (refresh_tokens table for character-user mapping)
     *
     * @param \Illuminate\Support\Collection $summaries
     * @return \Illuminate\Support\Collection
     */
    public function groupByMainCharacter($summaries, ?int $corporationId = null)
    {
        // Get all character IDs from mining data
        $characterIds = $summaries->pluck('character_id')->unique()->toArray();

        // Get user IDs for these characters from refresh_tokens table (SeAT v5.x standard)
        $characterUserMapping = DB::table('refresh_tokens')
            ->whereIn('character_id', $characterIds)
            ->select('character_id', 'user_id')
            ->get()
            ->pluck('user_id', 'character_id');

        // Also fetch ALL registered users for the corporation(s) so main accounts
        // with zero mining still appear in the summary
        $corpIds = $corporationId
            ? [$corporationId]
            : $summaries->pluck('corporation_id')->filter()->unique()->toArray();

        if (!empty($corpIds)) {
            // Find all character_ids affiliated with these corporations
            $allCorpCharIds = DB::table('character_affiliations')
                ->whereIn('corporation_id', $corpIds)
                ->pluck('character_id')
                ->toArray();

            // Map them to user IDs via refresh_tokens
            $allCorpUserMapping = DB::table('refresh_tokens')
                ->whereIn('character_id', $allCorpCharIds)
                ->select('character_id', 'user_id')
                ->get()
                ->pluck('user_id', 'character_id');

            // Merge into characterUserMapping (mining chars take precedence)
            foreach ($allCorpUserMapping as $charId => $userId) {
                if (!$characterUserMapping->has($charId)) {
                    $characterUserMapping[$charId] = $userId;
                }
            }
        }

        // Get main character IDs for all users
        $userIds = $characterUserMapping->values()->unique()->toArray();
        $mainCharacterMapping = DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('main_character_id', 'id');

        // Build character-to-main-character mapping
        $characterToMain = [];
        foreach ($characterUserMapping as $charId => $userId) {
            $mainCharId = $mainCharacterMapping->get($userId, $charId);
            $characterToMain[$charId] = $mainCharId;
        }

        // Group characters by their main character
        $groupedByMain = [];
        foreach ($summaries as $summary) {
            $charId = $summary->character_id;
            $mainCharId = $characterToMain[$charId] ?? $charId;

            if (!isset($groupedByMain[$mainCharId])) {
                $groupedByMain[$mainCharId] = collect();
            }
            $groupedByMain[$mainCharId]->push($summary);
        }

        // Add main characters with zero mining (not yet in groupedByMain)
        $allMainCharIds = collect($mainCharacterMapping->values())->unique();
        foreach ($allMainCharIds as $mainCharId) {
            if (!isset($groupedByMain[$mainCharId])) {
                $groupedByMain[$mainCharId] = collect();
            }
        }

        // Build final grouped summaries
        $grouped = collect();

        foreach ($groupedByMain as $mainCharId => $userSummaries) {
            if ($userSummaries->isEmpty()) {
                // Main account with zero mining — create an empty summary object
                $emptySummary = new \stdClass();
                $emptySummary->character_id = $mainCharId;
                $emptySummary->corporation_id = $corporationId;
                $emptySummary->total_value = 0;
                $emptySummary->total_quantity = 0;
                $emptySummary->moon_ore_value = 0;
                $emptySummary->regular_ore_value = 0;
                $emptySummary->ice_value = 0;
                $emptySummary->gas_value = 0;
                $emptySummary->abyssal_ore_value = 0;
                $emptySummary->triglavian_ore_value = 0;
                $emptySummary->moon_value = 0;
                $emptySummary->anomalies_value = 0;
                $emptySummary->total_volume_m3 = 0;
                $emptySummary->alt_characters = collect();
                $emptySummary->alt_count = 0;
                $emptySummary->ore_type_ids = [];
                $emptySummary->systems = collect();
                $emptySummary->primary_system = null;
                $emptySummary->system_count = 0;
                $grouped->push($emptySummary);
                continue;
            }

            // Find the main character's own summary (they may or may not have mined)
            $mainCharSummary = $userSummaries->where('character_id', $mainCharId)->first();

            if ($mainCharSummary) {
                // Main character mined — use their summary as the base, alts are the rest
                $mainSummary = $mainCharSummary;
                $mainSummary->alt_characters = $userSummaries->where('character_id', '!=', $mainCharId)->values();
            } else {
                // Main character didn't mine — create a stub with their character_id
                // so their name/portrait displays correctly. All miners are alts.
                $mainSummary = new \stdClass();
                $mainSummary->character_id = $mainCharId;
                $mainSummary->corporation_id = $userSummaries->first()?->corporation_id ?? $corporationId;
                $mainSummary->alt_characters = $userSummaries->values();
                $mainSummary->ore_type_ids = [];
                $mainSummary->systems = collect();
                $mainSummary->primary_system = null;
                $mainSummary->system_count = 0;
            }

            $mainSummary->alt_count = $mainSummary->alt_characters->count();

            // Aggregate totals from all characters (main + alts)
            $mainSummary->total_value = $userSummaries->sum('total_value');
            $mainSummary->total_quantity = $userSummaries->sum('total_quantity');
            $mainSummary->total_volume_m3 = $userSummaries->sum('total_volume_m3');
            $mainSummary->moon_ore_value = $userSummaries->sum('moon_ore_value');
            $mainSummary->regular_ore_value = $userSummaries->sum('regular_ore_value');
            $mainSummary->ice_value = $userSummaries->sum('ice_value');
            $mainSummary->gas_value = $userSummaries->sum('gas_value');
            $mainSummary->abyssal_ore_value = $userSummaries->sum('abyssal_ore_value');
            $mainSummary->triglavian_ore_value = $userSummaries->sum('triglavian_ore_value');
            $mainSummary->moon_value = $userSummaries->sum('moon_value');
            $mainSummary->anomalies_value = $userSummaries->sum('anomalies_value');

            // Merge ore types from all characters
            $allOreTypes = $userSummaries->pluck('ore_type_ids')->flatten()->unique()->values();
            $mainSummary->ore_type_ids = $allOreTypes->toArray();

            // Merge and re-aggregate systems from all characters
            $allSystems = $userSummaries->pluck('systems')->filter()->flatten();
            if ($allSystems->isNotEmpty()) {
                $systemsById = $allSystems->groupBy('solar_system_id')->map(function($group) {
                    $first = $group->first();
                    $first->system_value = $group->sum('system_value');
                    return $first;
                })->sortByDesc('system_value')->values();

                $mainSummary->systems = $systemsById;
                $mainSummary->primary_system = $systemsById->first();
                $mainSummary->system_count = $systemsById->count();
            }

            $grouped->push($mainSummary);
        }

        return $grouped->sortByDesc('total_value')->values();
    }
}
