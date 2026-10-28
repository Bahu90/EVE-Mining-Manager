<?php

namespace MiningManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The plugin's every-minute work, in one process.
 *
 * SeAT's scheduler starts a separate PHP process for every scheduled command,
 * and each one boots all of SeAT and Laravel. Three commands ran every minute
 * (chunk arrivals, mining events, character lookups), so three full boots a
 * minute. Running them here costs one.
 *
 * Each step keeps its own lock and its own error handling: one failing does
 * not stop the others. The time-sensitive steps run first; character lookups
 * call ESI and go last.
 */
class EveryMinuteCommand extends Command
{
    protected $signature = 'mining-manager:every-minute';

    protected $description = 'Run the every-minute jobs (chunk arrivals, mining events, character lookups) in one process';

    /** @var array<string, array> command => arguments */
    public const STEPS = [
        'mining-manager:check-extraction-arrivals' => [],
        'mining-manager:update-events' => [],
        'mining-manager:resolve-characters' => ['--requested' => true],
    ];

    public function handle(): int
    {
        $failed = 0;

        foreach (self::STEPS as $command => $arguments) {
            try {
                $code = $this->call($command, $arguments);
                if ($code !== self::SUCCESS) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error("Mining Manager: {$command} failed during every-minute run: " . $e->getMessage());
                $this->error("{$command} failed: " . $e->getMessage());
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
