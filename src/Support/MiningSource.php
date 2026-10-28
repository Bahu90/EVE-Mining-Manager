<?php

namespace MiningManager\Support;

/**
 * Where mining happened, as the pages can show it.
 *
 * - Moon: mined at the corporation's own refineries. These rows come from
 *   the refineries' mining data (they carry an observer_id), which is what
 *   the corporation's website counts.
 * - Anomalies: everything else in a pilot's own mining log: belts,
 *   anomalies, and moons at other corporations' refineries (no observer_id).
 * - All: both together.
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

        return match ($source) {
            self::MOON => $query->whereNotNull("{$table}.observer_id"),
            self::ANOMALIES => $query->whereNull("{$table}.observer_id"),
            default => $query,
        };
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

        return $observer ? self::MOON : self::ANOMALIES;
    }

    /**
     * SQL that sums a ledger column for one source, for queries that split
     * a total into Moon and Anomalies in one pass.
     */
    public static function sumSql(string $source, string $valueColumn = 'total_value', string $observerColumn = 'observer_id'): string
    {
        $condition = $source === self::MOON
            ? "{$observerColumn} IS NOT NULL"
            : "{$observerColumn} IS NULL";

        return "SUM(CASE WHEN {$condition} THEN {$valueColumn} ELSE 0 END)";
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
