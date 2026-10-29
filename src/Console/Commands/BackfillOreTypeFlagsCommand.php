<?php

namespace MiningManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MiningManager\Models\MiningLedger;
use MiningManager\Services\TypeIdRegistry;
use Illuminate\Support\Facades\DB;
use MiningManager\Services\OreClassifier;

class BackfillOreTypeFlagsCommand extends Command
{
    protected $signature = 'mining-manager:backfill-ore-types
                            {--batch=1000 : Number of records to process per batch}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Backfill ore-type classification flags (is_moon_ore, is_ice, is_gas, is_abyssal, is_triglavian) + ore_category for existing mining ledger entries';

        /**
     * Acquire the run lock, then do the work.
     *
     * Two of these running at once would duplicate effort at best and interleave
     * writes to the same rows at worst. Deliberately NOT named run(): Symfony's
     * Command already has one, and shadowing it breaks every artisan call.
     */
    public function handle()
    {
        $lock = Cache::lock('mining-manager:backfill-ore-types', 3600);

        if (! $lock->get()) {
            $this->warn('Another instance of this command is already running. Skipping.');

            return self::SUCCESS;
        }

        try {
            return $this->handleLocked();
        } finally {
            $lock->release();
        }
    }

    private function handleLocked()
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║   Mining Manager - Backfill Ore Type Flags                ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->line('');

        $batchSize = $this->option('batch');
        $dryRun = (bool) $this->option('dry-run');
        $query = MiningLedger::query();

        $total = (clone $query)->count();
        $this->info("📊 Found {$total} total ledger entries");

        if ($total === 0) {
            $this->warn('⚠️  Nothing to process.');
            return Command::SUCCESS;
        }

        $this->line('');
        $this->info($dryRun ? '🔍 Dry run, nothing will be written...' : '🔄 Processing entries...');

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        // Process in batches to avoid memory issues
        $changesByCategory = [];

        $query->chunkById($batchSize, function ($entries) use (&$updated, &$errors, &$changesByCategory, $progressBar, $dryRun) {
            foreach ($entries as $entry) {
                try {
                    // Classify ore type using TypeIdRegistry — same logic as
                    // ProcessMiningLedgerCommand uses on initial ingestion, kept
                    // in sync so backfill produces the exact same classifications.
                    $isMoonOre = TypeIdRegistry::isMoonOre($entry->type_id);
                    $isIce = TypeIdRegistry::isIce($entry->type_id);
                    $isGas = TypeIdRegistry::isGas($entry->type_id);
                    $isAbyssal = OreClassifier::isAbyssal($entry->type_id);
                    $isTriglavian = TypeIdRegistry::isTriglavianOre($entry->type_id);
                    $oreCategory = $this->classifyOreCategory(
                        $entry->type_id,
                        $isMoonOre, $isIce, $isGas, $isAbyssal, $isTriglavian
                    );

                    // Only update if values have changed (avoids touching
                    // updated_at on already-correct rows)
                    if ($entry->is_moon_ore != $isMoonOre ||
                        $entry->is_ice != $isIce ||
                        $entry->is_gas != $isGas ||
                        $entry->is_abyssal != $isAbyssal ||
                        $entry->is_triglavian != $isTriglavian ||
                        $entry->ore_category !== $oreCategory) {

                        // Worth seeing which way rows are moving.
                        $from = $entry->ore_category ?? 'null';
                        $key = $from . ' -> ' . $oreCategory;
                        $changesByCategory[$key] = ($changesByCategory[$key] ?? 0) + 1;

                        if (! $dryRun) {
                            $entry->update([
                                'is_moon_ore' => $isMoonOre,
                                'is_ice' => $isIce,
                                'is_gas' => $isGas,
                                'is_abyssal' => $isAbyssal,
                                'is_triglavian' => $isTriglavian,
                                'ore_category' => $oreCategory,
                            ]);
                        }

                        $updated++;
                    }

                    $progressBar->advance();

                } catch (\Exception $e) {
                    $this->error("\n❌ Error processing entry ID {$entry->id}: {$e->getMessage()}");
                    $errors++;
                    $progressBar->advance();
                }
            }
        });

        $progressBar->finish();
        $this->line('');
        $this->line('');

        // Summary
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                         SUMMARY                            ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info("║  ✅ Total processed:  {$total}");
        $this->info('║  🔄 ' . ($dryRun ? 'Would update:     ' : 'Updated:          ') . $updated);
        $this->info("║  ⏭️  Unchanged:        " . ($total - $updated - $errors));
        $this->info("║  ❌ Errors:           {$errors}");
        $this->info('╚════════════════════════════════════════════════════════════╝');

        if (! empty($changesByCategory)) {
            $this->line('');
            $this->info('Category movements:');
            ksort($changesByCategory);
            $rows = [];
            foreach ($changesByCategory as $move => $count) {
                $rows[] = explode(' -> ', $move) + [2 => null];
                $rows[count($rows) - 1][2] = $count;
            }
            $this->table(['From', 'To', 'Rows'], $rows);
            $this->line('A row moving between categories is charged at a different rate.');
        }

        if ($dryRun) {
            $this->line('');
            $this->comment('Dry run. Re-run without --dry-run to apply.');
        }

        return Command::SUCCESS;
    }

    /**
     * Classify an ore into its category string for the `ore_category` column.
     * Mirrors ProcessMiningLedgerCommand::classifyOreCategory but takes the
     * pre-computed flags so we don't double-call TypeIdRegistry checks.
     *
     * Used by analytics filters and dashboard ore-mix charts. Output values:
     *   moon_r4 / moon_r8 / moon_r16 / moon_r32 / moon_r64 / moon
     *   ice
     *   gas
     *   abyssal
     *   triglavian
     *   ore (catch-all for vanilla regular ores)
     *
     * @param int  $typeId
     * @param bool $isMoonOre
     * @param bool $isIce
     * @param bool $isGas
     * @param bool $isAbyssal
     * @param bool $isTriglavian
     * @return string
     */
    private function classifyOreCategory(int $typeId, bool $isMoonOre, bool $isIce, bool $isGas, bool $isAbyssal, bool $isTriglavian): string
    {
        if ($isMoonOre) {
            $rarity = TypeIdRegistry::getMoonOreRarity($typeId);
            return $rarity ? 'moon_' . $rarity : 'moon';
        }
        if ($isIce) return 'ice';
        if ($isGas) return 'gas';
        if ($isAbyssal) return 'abyssal';
        if ($isTriglavian) return 'triglavian';
        return 'ore';
    }
}
