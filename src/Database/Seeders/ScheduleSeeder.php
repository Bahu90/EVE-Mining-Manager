<?php

namespace MiningManager\Database\Seeders;

use Seat\Services\Seeding\AbstractScheduleSeeder;

/**
 * SeAT v5 schedule seeder for Mining Manager's cron commands.
 *
 * Inherits the canonical `firstOrCreate` semantics from
 * `AbstractScheduleSeeder::run()`: NEW installs get every schedule from
 * `getSchedules()` inserted into `schedules`; existing installs keep
 * whatever's already there. This is the pattern every SeAT plugin uses.
 *
 * Why we don't override `run()` to force-update existing rows:
 *   - The schedules table is part of the operator's control surface.
 *     Operators routinely customise cron expressions for their install
 *     (different timezone, less aggressive ESI rate, paused commands by
 *     setting expression='', etc.). Forcibly overwriting those on every
 *     plugin boot is user-hostile and contrary to SeAT conventions.
 *   - `AbstractScheduleSeeder` is explicit about the no-reconciliation
 *     contract.
 *
 * For deprecation (renaming/removing a command), use
 * `getDeprecatedSchedules()` below — `AbstractScheduleSeeder::run()`
 * deletes those rows during the seed pass.
 *
 * For changing an existing command's cron expression in a future release:
 * the canonical pattern is "old → deprecated, new → fresh insert" via the
 * two methods. The plugin SHOULD NOT silently rewrite cron rows that an
 * operator may have intentionally customised.
 */
class ScheduleSeeder extends AbstractScheduleSeeder
{
    /**
     * Returns a list of schedules to be added to the schedule table.
     *
     * @return array
     */
    public function getSchedules(): array
    {
        return [
            // Every minute, in one process (EveryMinuteCommand): chunk arrival
            // and unstable notices, mining event status changes, and lookups
            // for the characters pages asked about. They used to be three
            // schedules, so SeAT booted three PHP processes every minute.
            [
                'command' => 'mining-manager:every-minute',
                'expression' => '* * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Characters SeAT does not know are looked up in the background, so
            // no page waits on ESI: every 10 minutes also miners SeAT has no
            // affiliation for (the ones pages asked for run every minute above).
            [
                'command' => 'mining-manager:resolve-characters',
                'expression' => '*/10 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Process corporation observer mining - runs every 30 minutes at :15 and :45
            [
                'command' => 'mining-manager:process-ledger',
                'expression' => '15,45 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Import character mining from SeAT ESI cache - runs every 30 minutes at :20 and :50
            // --days=2 on purpose: mining SeAT saves later than that is left out so billed days stay put
            [
                'command' => 'mining-manager:import-character-mining --days=2',
                'expression' => '20,50 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Update moon extractions - runs every 2 hours to catch late fracture notifications
            [
                'command' => 'mining-manager:update-extractions',
                'expression' => '0 */2 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Generate previous month's report — runs day 9 at 04:05 AM.
            // For ad-hoc/user-defined report cadences, use `report_schedules` (handled by cron below).
            [
                'command' => 'mining-manager:generate-reports',
                'expression' => '5 4 9 * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Process scheduled reports - runs every hour to check for due schedules
            [
                'command' => 'mining-manager:generate-reports --scheduled',
                'expression' => '0 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Cache price data - runs every 4 hours at :30 past
            // (Reduces API load on providers like Janice while keeping prices reasonably fresh)
            [
                'command' => 'mining-manager:cache-prices',
                'expression' => '30 */4 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Recalculate extraction values - runs twice daily at 6 AM and 6 PM
            // (Updates values 4 hours before chunk arrival with current market prices)
            [
                'command' => 'mining-manager:recalculate-extraction-values',
                'expression' => '0 6,18 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Archive old extractions - runs daily at 5:05 AM (staggered)
            // (Archives completed extractions older than 7 days, keeps 12 months)
            [
                'command' => 'mining-manager:archive-extractions',
                'expression' => '5 5 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Finalize month summaries - runs on 2nd of each month at 2:00 AM.
            // Runs on 2nd (not 1st) to allow observer data to settle before finalizing.
            [
                'command' => 'mining-manager:finalize-month',
                'expression' => '0 2 2 * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Calculate monthly statistics - runs on 2nd of each month at 3:00 AM
            // Pre-calculates dashboard stats for the now-closed previous month.
            [
                'command' => 'mining-manager:calculate-monthly-stats',
                'expression' => '0 3 2 * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Update ledger prices - runs daily at 1 AM (after cache-prices at 0:30)
            // Locks in daily session prices for today's mining entries
            [
                'command' => 'mining-manager:update-ledger-prices',
                'expression' => '0 1 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Update daily summaries (safety net) - runs daily at 1:30 AM (before finalize)
            // Catches non-observer mining (belt mining, etc.) and any late ESI data.
            // Must run before finalize-month so it works from complete data.
            [
                'command' => 'mining-manager:update-daily-summaries',
                'expression' => '30 1 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Update current month dashboard statistics - runs every 30 min
            // Reads from pre-computed daily summaries (fast) and updates monthly_statistics
            // Pipeline: :15/:45 process-ledger (+ auto daily summaries) → :20/:50 monthly stats
            [
                'command' => 'mining-manager:calculate-monthly-stats --current-month',
                // :25 and :55, after process-ledger (:15/:45) and the personal
                // import (:20/:50) have written, instead of at the same minute
                // as the personal import.
                'expression' => '25,55 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Detect jackpots - runs daily at 6:05 AM (staggered after recalculate-extraction-values)
            // (Analyzes recent moon extractions to identify high-value jackpot ores)
            [
                'command' => 'mining-manager:detect-jackpots',
                'expression' => '5 6 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Scan Metenox MoonMaterialBays for cross-up transitions above the
            // configurable fill-% threshold (default 85). Fires the
            // metenox_cargo_full notification once per crossing; latch in
            // metenox_cargo_alert_state prevents repeats while still over
            // threshold. 5-min cadence matches the corp-assets ESI cache
            // (operator's perceived latency is ~5min from threshold-cross to
            // ping). Reads only corporation_assets, which SeAT itself
            // populates.
            [
                'command' => 'mining-manager:scan-metenox-cargo-fill',
                'expression' => '*/5 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            // Daily integrity audit of moon_extractions.status against the
            // model's helper-computed lifecycle position. Catches divergence
            // between the import-time determineStatus() path and the runtime
            // helpers (scopeExpiredByTime / getExpiryTime). Added 2026-05-31
            // as a permanent backstop after the natural_decay_time vs
            // fractured_at+50h bug ate every moon_chunk_unstable notification
            // in production for an unknown stretch of time.
            //
            // 03:00 UTC — quiet hour, off the top of the hour to avoid
            // colliding with the */2-hour update-extractions sweep at :00.
            // Read-only by default; the --quiet-ok flag silences output when
            // there's nothing to report (so the daily cron line stays a
            // no-op when everything is healthy).
            [
                'command' => 'mining-manager:validate-lifecycle-integrity --quiet-ok',
                'expression' => '0 3 * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
        ];
    }

    /**
     * Returns a list of commands to remove from the schedule.
     * This is useful for cleanup when commands are renamed or deprecated.
     *
     * @return array
     */
    public function getDeprecatedSchedules(): array
    {
        return [
            'mining-manager:scan-corporation-contracts', // Removed
            'mining-manager:update-daily-summaries --today-only', // Removed: process-ledger now auto-generates daily summaries
            // Moved into mining-manager:every-minute (one process instead of three)
            'mining-manager:check-extraction-arrivals',
            'mining-manager:update-events',
            'mining-manager:resolve-characters --requested',
            // Retired: it only published moon lifecycle events for another
            // plugin to pick up. Mining Manager now stands on its own.
            'mining-manager:scan-extraction-events',
            // Removed with the tax and theft features.
            'mining-manager:send-reminders',
            'mining-manager:calculate-taxes',
            'mining-manager:generate-invoices',
            'mining-manager:verify-payments --auto-match',
            'mining-manager:verify-ore-contracts',
            'mining-manager:send-outstanding-digest',
            'mining-manager:detect-theft --notify',
            'mining-manager:monitor-active-thefts --notify',
        ];
    }
}
