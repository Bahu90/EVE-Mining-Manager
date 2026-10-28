<?php

namespace MiningManager\Services\Metrics;

use Carbon\Carbon;

/**
 * The arithmetic behind the Mining Metrics page, kept free of the database
 * so it can be tested on its own.
 */
final class MetricsMath
{
    /**
     * Months ending with $month, oldest first, as "Y-m" strings.
     *
     * @return string[]
     */
    public static function monthsEndingWith(Carbon $month, int $count): array
    {
        $count = max(1, $count);
        $start = $month->copy()->startOfMonth()->subMonths($count - 1);
        $months = [];

        for ($i = 0; $i < $count; $i++) {
            $months[] = $start->copy()->addMonths($i)->format('Y-m');
        }

        return $months;
    }

    /**
     * Every day of a month as "Y-m-d", stopping at today for the current month.
     *
     * @return string[]
     */
    public static function daysOf(Carbon $month, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now())->copy()->startOfDay();
        $day = $month->copy()->startOfMonth();
        $last = $month->copy()->endOfMonth()->startOfDay();

        if ($last->gt($today) && $day->lte($today)) {
            $last = $today;
        }

        $days = [];
        while ($day->lte($last)) {
            $days[] = $day->format('Y-m-d');
            $day->addDay();
        }

        return $days;
    }

    /**
     * Spread keyed sums over a fixed list of keys, filling gaps with zero.
     *
     * @param string[] $keys
     * @param array<string, array{isk:float,tax:float,volume:float}> $byKey
     * @return array{labels:string[], isk:float[], tax:float[], volume:float[]}
     */
    public static function series(array $keys, array $byKey): array
    {
        $out = ['labels' => $keys, 'isk' => [], 'tax' => [], 'volume' => []];

        foreach ($keys as $key) {
            $row = $byKey[$key] ?? [];
            $out['isk'][] = round((float) ($row['isk'] ?? 0), 2);
            $out['tax'][] = round((float) ($row['tax'] ?? 0), 2);
            $out['volume'][] = round((float) ($row['volume'] ?? 0), 2);
        }

        return $out;
    }

    /**
     * Fold per-character totals into one entry per player (their main), so a
     * pilot with alts is one card. Characters with no SeAT account stand on
     * their own and are marked as guests.
     *
     * @param array<int, array{isk:float,tax:float,volume:float,quantity:float}> $byCharacter character_id => totals
     * @param array<int, int> $mainOf character_id => main character_id (registered characters only)
     * @param array<int, string> $names character_id => name
     * @return array<int, array> sorted by volume, largest first
     */
    public static function players(array $byCharacter, array $mainOf, array $names): array
    {
        $players = [];

        foreach ($byCharacter as $characterId => $totals) {
            $characterId = (int) $characterId;
            $registered = isset($mainOf[$characterId]);
            $mainId = $registered ? (int) $mainOf[$characterId] : $characterId;

            if (!isset($players[$mainId])) {
                $players[$mainId] = [
                    'character_id' => $mainId,
                    'name' => $names[$mainId] ?? ($names[$characterId] ?? (string) $mainId),
                    'guest' => !$registered,
                    'characters' => 0,
                    'isk' => 0.0,
                    'tax' => 0.0,
                    'volume' => 0.0,
                    'quantity' => 0.0,
                ];
            }

            $players[$mainId]['characters']++;
            $players[$mainId]['isk'] += (float) ($totals['isk'] ?? 0);
            $players[$mainId]['tax'] += (float) ($totals['tax'] ?? 0);
            $players[$mainId]['volume'] += (float) ($totals['volume'] ?? 0);
            $players[$mainId]['quantity'] += (float) ($totals['quantity'] ?? 0);
        }

        $players = array_values($players);
        usort($players, function ($a, $b) {
            return [$b['volume'], $b['isk'], $a['name']] <=> [$a['volume'], $a['isk'], $b['name']];
        });

        return $players;
    }

    /**
     * Refinery status counts for the ring: ready (a chunk is on the field),
     * extracting (a drill is running) and idle (neither).
     *
     * @param string[] $statuses one effective status per refinery:
     *                           'ready', 'unstable', 'extracting', or anything else for idle
     * @return array{online:int, ready:int, extracting:int, idle:int}
     */
    public static function refineryCounts(array $statuses): array
    {
        $counts = ['online' => count($statuses), 'ready' => 0, 'extracting' => 0, 'idle' => 0];

        foreach ($statuses as $status) {
            if ($status === 'ready' || $status === 'unstable') {
                $counts['ready']++;
            } elseif ($status === 'extracting') {
                $counts['extracting']++;
            } else {
                $counts['idle']++;
            }
        }

        return $counts;
    }

    /**
     * The key a grouped ledger sum is filed under: a date cut to its day, a
     * month as it is, an ID whole.
     *
     * @param mixed $value
     */
    public static function groupKey($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = (string) $value;

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? substr($value, 0, 10) : $value;
    }
}
