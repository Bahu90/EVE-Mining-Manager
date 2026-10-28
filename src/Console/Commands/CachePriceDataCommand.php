<?php

namespace MiningManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MiningManager\Console\Commands\Concerns\RunsWithinABudget;
use MiningManager\Services\Pricing\PriceProviderService;
use MiningManager\Services\Pricing\MarketDataService;
use MiningManager\Services\Configuration\SettingsManagerService;
use MiningManager\Services\TypeIdRegistry;
use MiningManager\Models\MiningPriceCache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CachePriceDataCommand extends Command
{
    use RunsWithinABudget;

    /**
     * How long a refresh may spend asking the provider for prices.
     *
     * The staged retreat turns one bad batch of a hundred into fifteen
     * requests, each on its own thirty second timeout, so a provider that
     * hangs rather than refuses can keep a full refresh going for the better
     * part of an hour. Stopping early costs nothing: ids we did not reach keep
     * the price they already had, and the next run is four hours away.
     */
    public const FETCH_BUDGET_SECONDS = 480;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mining-manager:cache-prices
                            {--type=all : Type to cache (ore|compressed-ore|moon|materials|minerals|ice|ice-products|gas|compressed|all)}
                            {--region=10000002 : Region ID (default: The Forge)}
                            {--force : Refresh every price, including ones refreshed recently}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache market price data for mining materials';

    /**
     * Price provider service
     *
     * @var PriceProviderService
     */
    protected $priceService;

    /**
     * Market data service
     *
     * @var MarketDataService
     */
    protected $marketService;

    /**
     * Settings manager service
     *
     * @var SettingsManagerService
     */
    protected $settingsService;

    /**
     * Create a new command instance.
     *
     * @param PriceProviderService $priceService
     * @param MarketDataService $marketService
     * @param SettingsManagerService $settingsService
     */
    public function __construct(PriceProviderService $priceService, MarketDataService $marketService, SettingsManagerService $settingsService)
    {
        parent::__construct();
        $this->priceService = $priceService;
        $this->marketService = $marketService;
        $this->settingsService = $settingsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $lock = $this->lockForBudget('mining-manager:cache-prices', self::FETCH_BUDGET_SECONDS);
        if (!$lock) {
            $this->warn('Another instance of this command is already running. Skipping.');
            return Command::SUCCESS;
        }

        try {
            $this->info('Starting price cache update...');

            $type = $this->option('type');
            $regionId = $this->option('region');
            $force = $this->option('force');

            $this->info("Caching prices for: {$type}");
            $this->info("Region ID: {$regionId}");

            // Get type IDs to cache
            $typeIds = $this->getTypeIdsForCategory($type);

            if (empty($typeIds)) {
                $this->error("No type IDs found for category: {$type}");
                return Command::FAILURE;
            }

            $this->info("Found " . count($typeIds) . " items to cache");

            $this->priceService->stopFetchingAfter($this->budgetEndsAt());

            try {
                $this->fetchFromProvider($typeIds, $regionId, $force);
            } finally {
                $this->priceService->stopFetchingAfter(null);
            }

            // Clean up old cache entries
            $this->cleanupOldCache();

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * Fetch prices from the configured provider (SeAT, Janice, Fuzzwork)
     *
     * @param array $typeIds
     * @param int $regionId
     * @param bool $force
     * @return void
     */
    private function fetchFromProvider(array $typeIds, int $regionId, bool $force): void
    {
        $cached = 0;
        $skipped = 0;
        $errors = 0;

        // Skip only prices refreshed within the last half of the cache
        // duration. A scheduled run meets the previous run's prices a few
        // seconds short of one interval old, which with the default schedule
        // and duration is still inside the duration, so a plain freshness
        // test skips them there and they get refreshed only every second run.
        $cacheMinutes = (int) ($this->settingsService->getPricingSettings()['cache_duration'] ?? 240);
        $recentlyCached = $force
            ? []
            : array_flip($this->priceService->typeIdsCachedSince(
                $regionId,
                Carbon::now()->subSeconds($cacheMinutes * 30)
            ));

        $due = [];
        foreach ($typeIds as $typeId) {
            if (isset($recentlyCached[$typeId])) {
                $skipped++;
                continue;
            }

            $due[] = (int) $typeId;
        }

        // Ask for the whole list in one go and let the provider decide how to
        // split it. Janice takes a hundred ids per request, so a refresh is a
        // handful of requests rather than one per price.
        $priced = [];
        if (!empty($due)) {
            $this->line('Asking the price provider for ' . count($due) . ' price(s)...');

            try {
                $priced = $this->marketService->getCachedPrices($due, $force);
            } catch (\Exception $e) {
                $this->error('  The price provider failed: ' . $e->getMessage());
                $this->warn('  Cached prices are left as they are.');
                $errors = count($due);
                $priced = [];
            }
        }

        // While the provider is down, prices come from its fallback, and what
        // that cannot price comes back at its cached price. Writing those back
        // would only make stale prices look fresh, so they are left alone. A
        // type that never had a price gets SeAT's, and that is written.
        $keptIds = [];
        $kept = 0;
        if ($this->priceService->lastFetchStoodIn()) {
            $keptIds = array_flip($this->priceService->lastKeptTypeIds());
            $fallback = $this->priceService->lastFallbackProvider();
            $this->warn($fallback
                ? '  The price provider failed, so prices came from ' . PriceProviderService::PROVIDER_LABELS[$fallback] . ' instead.'
                : '  The price provider failed, so cached prices are left as they are.');
            $fromSeat = count($this->priceService->lastSeatStandInTypeIds());
            if ($fromSeat > 0) {
                $this->line("  {$fromSeat} type(s) with no price yet were priced from SeAT's own market data.");
            }
        }

        $bar = $this->output->createProgressBar(count($due));
        $bar->start();

        $missing = [];
        foreach ($due as $typeId) {
            $price = (float) ($priced[$typeId] ?? 0);

            if (isset($keptIds[$typeId])) {
                $kept++;
            } elseif ($price > 0) {
                $this->priceService->cachePriceData($typeId, $regionId, [
                    'sell' => $price,
                    'buy' => $price,
                    'average' => $price,
                ]);
                $cached++;
            } elseif ($errors === 0) {
                // No price this time. Whatever is cached stays, so this is
                // worth naming but is not an error on its own.
                $missing[] = $typeId;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (!empty($missing)) {
            $this->warn('No price came back for ' . count($missing) . ' type(s); their cached prices are unchanged.');
            $this->line('  ' . implode(', ', array_slice($missing, 0, 20)) . (count($missing) > 20 ? ', ...' : ''));
        }

        $this->info("Price cache update complete!");
        $this->info("Cached: {$cached} items");
        if ($skipped > 0) {
            $this->info("Skipped: {$skipped} (refreshed within the last half of the cache duration)");
        }
        if ($kept > 0) {
            $this->info("Kept: {$kept} cached price(s), unchanged while the provider is down");
        }
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }
    }

    /**
     * Get type IDs for a category
     * Now uses TypeIdRegistry as single source of truth
     *
     * @param string $category
     * @return array
     */
    private function getTypeIdsForCategory(string $category): array
    {
        return TypeIdRegistry::getTypeIdsByCategory($category);
    }

    /**
     * Clean up old cache entries
     * Removes entries older than 7 days
     *
     * @return void
     */
    private function cleanupOldCache()
    {
        $this->info('Cleaning up old cache entries...');
        
        try {
            $cutoffDate = Carbon::now()->subDays(7);
            
            $deleted = MiningPriceCache::where('updated_at', '<', $cutoffDate)->delete();
            
            if ($deleted > 0) {
                $this->info("Removed {$deleted} old cache entries (older than 7 days)");
            } else {
                $this->info("No old cache entries found");
            }
        } catch (\Exception $e) {
            $this->warn("Could not clean up cache: {$e->getMessage()}");
        }
    }
}
