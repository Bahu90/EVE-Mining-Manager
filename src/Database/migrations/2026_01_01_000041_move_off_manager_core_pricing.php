<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Mining Manager no longer works with Manager Core, so it can no longer be
 * the price provider or the fallback provider.
 *
 * Without this, an install that had picked Manager Core would quietly drop
 * to SeAT's price averages, which are far from what ore sells for. It moves
 * to Fuzzwork instead: free, no key, and pricing at the same trade hubs.
 * A fallback of Manager Core becomes Fuzzwork too, or none when Fuzzwork is
 * now the provider itself.
 *
 * Forward-only: Manager Core is not an option to go back to.
 */
class MoveOffManagerCorePricing extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mining_manager_settings')) {
            return;
        }

        $moved = DB::table('mining_manager_settings')
            ->where('key', 'price_provider')
            ->where('value', 'manager-core')
            ->update(['value' => 'fuzzwork', 'updated_at' => now()]);

        // Per corporation: a fallback equal to its provider means no fallback.
        $fallbacks = DB::table('mining_manager_settings')
            ->where('key', 'pricing.fallback_provider')
            ->where('value', 'manager-core')
            ->get(['id', 'corporation_id']);

        foreach ($fallbacks as $row) {
            $provider = DB::table('mining_manager_settings')
                ->where('key', 'price_provider')
                ->where('corporation_id', $row->corporation_id)
                ->value('value');

            DB::table('mining_manager_settings')->where('id', $row->id)->update([
                'value' => $provider === 'fuzzwork' ? 'none' : 'fuzzwork',
                'updated_at' => now(),
            ]);
        }

        // Settings only Manager Core read.
        DB::table('mining_manager_settings')
            ->whereIn('key', [
                'pricing.manager_core_market',
                'pricing.manager_core_variant',
                'manager_core_market',
                'manager_core_variant',
                'notifications.moon_extraction_fastpoll_mode',
            ])
            ->delete();

        if ($moved > 0 || $fallbacks->isNotEmpty()) {
            Log::info('Mining Manager: moved price settings off Manager Core', [
                'providers_now_fuzzwork' => $moved,
                'fallbacks_changed' => $fallbacks->count(),
            ]);
        }
    }

    public function down(): void
    {
        // Nothing to restore: Manager Core is no longer supported.
    }
}
