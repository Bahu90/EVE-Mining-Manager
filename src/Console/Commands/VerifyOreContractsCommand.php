<?php

namespace MiningManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MiningManager\Services\Tax\Ore\OreContractService;

/**
 * Credit mining tax paid in ore: item exchange contracts members made to a
 * corporation that takes its tax in ore. See OreContractService.
 */
class VerifyOreContractsCommand extends Command
{
    protected $signature = 'mining-manager:verify-ore-contracts
                            {--dry-run : Show what would be credited without writing anything}';

    protected $description = 'Credit ore tax contracts members made to the corporation';

    public function handle(OreContractService $contracts): int
    {
        $lock = Cache::lock('mining-manager:verify-ore-contracts', 900);
        if (!$lock->get()) {
            $this->warn('Another check is already running. Skipping.');

            return self::SUCCESS;
        }

        try {
            $summary = $contracts->check((bool) $this->option('dry-run'));

            if ($summary['corporations'] === 0) {
                $this->info('No corporation takes its tax in ore. Nothing to do.');

                return self::SUCCESS;
            }

            $this->info(sprintf(
                '%s %d contract(s) across %d corporation(s): %d credited (%s ISK), %d waiting, %d expired or refused, %d with nothing to credit.',
                $this->option('dry-run') ? 'Would process' : 'Processed',
                $summary['seen'],
                $summary['corporations'],
                $summary['credited'],
                number_format($summary['isk'], 0),
                $summary['waiting'],
                $summary['refused'],
                $summary['empty']
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
