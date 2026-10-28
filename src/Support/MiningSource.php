<?php

namespace MiningManager\Support;

/**
 * Where mining happened, as the pages can show it.
 *
 * - Moon: moon ore. Mostly the refineries' own mining data (rows with an
 *   observer_id), plus moon ore in pilots' own logs that no refinery row
 *   covers: mining the refinery data has not reached yet, or another
 *   corporation's moon.
 * - Anomalies: everything else in pilots' own logs: ore, ice and gas from
 *   belts and anomalies. Moon ore never comes from there.
 * - All: both together.
 *
 * A pilot's log repeats what the refinery reported for the same pilot, day
 * and ore. When both are there the refinery row is counted and the log row
 * is not, so nothing is counted twice.
 *
 * Only what the pages show follows this. Tax is worked out from the tax
 * settings, whatever is picked here.
 *
 * The pick is remembered for the session, so moving between pages keeps it.
 */
final class MiningSource
{
    public const MOON = 'moon';
    public const ANOMALIES = 'anomalies';
    public const ALL = 'all';

    public const SOURCES = [self::MOON, self::ANOMALIES, self::ALL];

    public const DEFAULT = self::MOON;

    public const PARAM = 'source';

    private const SESSION_KEY = 'mining-manager.mining_source';

    private static ?int $owner = null;

    private static bool $ownerRead = false;

    /**
     * The source asked for in the request, else the one picked earlier in
     * the session, else Moon. A pick in the request is remembered.
     */
    public static function current(): string
    {
        $request = request();
        $asked = $request ? $request->query(self::PARAM) : null;

        if (is_string($asked) && self::isValid($asked)) {
            self::remember($asked);

            return $asked;
        }

        try {
            $stored = session(self::SESSION_KEY);
        } catch (\Throwable $e) {
            $stored = null;
        }

        return self::normalise(is_string($stored) ? $stored : null);
    }

    public static function isValid(?string $source): bool
    {
        return in_array($source, self::SOURCES, true);
    }

    public static function normalise(?string $source): string
    {
        return self::isValid($source) ? $source : self::DEFAULT;
    }

    /**
     * Limit a ledger query to the rows the pages count, from one source.
     *
     * Counted rows are the ones the daily summaries count: priced rows, from
     * the Moon Owner Corporation's refineries or from pilots' own logs.
     * Other corporations' refineries are left out, as the summaries leave
     * them out. All is then exactly Moon plus Anomalies.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string $table the ledger table or its alias in the query
     */
    public static function scope($query, string $source, string $table = 'mining_ledger')
    {
        $query->whereNotNull("{$table}.processed_at");

        $owner = self::moonOwner();
        if ($owner !== null) {
            $query->where(function ($q) use ($table, $owner) {
                $q->whereNull("{$table}.corporation_id")
                    ->orWhere("{$table}.corporation_id", $owner);
            });
        }

        return $query->whereRaw(self::condition($source, $table));
    }

    /**
     * SQL that is true for a ledger row of one source.
     *
     * @param string $table the ledger table or its alias in the query
     */
    public static function condition(string $source, string $table = 'mining_ledger'): string
    {
        $covered = self::coveredByRefinery($table);

        return match ($source) {
            self::MOON => "({$table}.observer_id IS NOT NULL OR ({$table}.is_moon_ore = 1 AND NOT {$covered}))",
            self::ANOMALIES => "({$table}.observer_id IS NULL AND {$table}.is_moon_ore = 0)",
            default => "({$table}.observer_id IS NOT NULL OR {$table}.is_moon_ore = 0 OR NOT {$covered})",
        };
    }

    /**
     * True for a log row whose pilot, day and ore the Moon Owner
     * Corporation's refinery data already has.
     */
    private static function coveredByRefinery(string $table): string
    {
        $owner = self::moonOwner();
        $ownerSql = $owner !== null ? ' AND mm_obs.corporation_id = ' . (int) $owner : '';

        return "EXISTS (SELECT 1 FROM mining_ledger mm_obs"
            . " WHERE mm_obs.character_id = {$table}.character_id"
            . " AND mm_obs.date = {$table}.date"
            . " AND mm_obs.type_id = {$table}.type_id"
            . " AND mm_obs.observer_id IS NOT NULL{$ownerSql})";
    }

    /**
     * The Moon Owner Corporation, read once per request.
     */
    public static function moonOwner(): ?int
    {
        if (self::$ownerRead) {
            return self::$owner;
        }

        self::$ownerRead = true;

        try {
            $value = app(\MiningManager\Services\Configuration\SettingsManagerService::class)
                ->getSettingForCorporation('general.moon_owner_corporation_id', null);
        } catch (\Throwable $e) {
            $value = null;
        }

        return self::$owner = $value ? (int) $value : null;
    }

    /**
     * For tests: forget the Moon Owner Corporation read earlier.
     */
    public static function forget(?int $owner = null, bool $read = false): void
    {
        self::$owner = $owner;
        self::$ownerRead = $read;
    }

    /**
     * Which source one ledger row belongs to.
     */
    public static function ofRow($row): string
    {
        $observer = is_array($row) ? ($row['observer_id'] ?? null) : ($row->observer_id ?? null);
        $moonOre = is_array($row) ? ($row['is_moon_ore'] ?? false) : ($row->is_moon_ore ?? false);

        return ($observer || $moonOre) ? self::MOON : self::ANOMALIES;
    }

    /**
     * SQL that sums a ledger column for one source, for queries that split
     * a total into Moon and Anomalies in one pass.
     */
    public static function sumSql(string $source, string $table = 'mining_ledger', string $valueColumn = 'total_value'): string
    {
        return 'SUM(CASE WHEN ' . self::condition($source, $table) . " THEN {$table}.{$valueColumn} ELSE 0 END)";
    }

    private static function remember(string $source): void
    {
        try {
            session([self::SESSION_KEY => $source]);
        } catch (\Throwable $e) {
            // No session, as in a console run: nothing to remember it in.
        }
    }
}
