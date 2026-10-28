<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move calculate-monthly-stats --current-month from :20/:50 to :25/:55.
 *
 * It ran in the same minute as import-character-mining (:20/:50), so the two
 * competed for the database and the stats were built before the personal
 * mining for that half hour had landed. The schedule seeder only creates
 * missing rows and never changes an existing one, so the row is moved here,
 * and only when it still has the old default: a time an admin chose is kept.
 */
class StaggerMiningManagerMonthlyStatsSchedule extends Migration
{
    private const COMMAND = 'mining-manager:calculate-monthly-stats --current-month';

    public function up(): void
    {
        $this->move('20,50 * * * *', '25,55 * * * *');
    }

    public function down(): void
    {
        $this->move('25,55 * * * *', '20,50 * * * *');
    }

    private function move(string $from, string $to): void
    {
        if (!Schema::hasTable('schedules')) {
            return;
        }

        DB::table('schedules')
            ->where('command', self::COMMAND)
            ->where('expression', $from)
            ->update(['expression' => $to]);
    }
}
