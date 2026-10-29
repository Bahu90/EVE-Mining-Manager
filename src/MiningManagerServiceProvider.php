<?php

namespace MiningManager;

use Seat\Services\AbstractSeatPlugin;
use MiningManager\Console\Commands\ProcessMiningLedgerCommand;
use MiningManager\Console\Commands\ReconcilePersonalMiningCommand;
use MiningManager\Console\Commands\BackfillOreTypeFlagsCommand;
use MiningManager\Console\Commands\CalculateMonthlyTaxesCommand;
use MiningManager\Console\Commands\CalculateMonthlyStatisticsCommand;
use MiningManager\Console\Commands\GenerateTaxInvoicesCommand;
use MiningManager\Console\Commands\UpdateMiningEventsCommand;
use MiningManager\Console\Commands\GenerateReportsCommand;
use MiningManager\Console\Commands\VerifyWalletPaymentsCommand;
use MiningManager\Console\Commands\SendOutstandingDigestCommand;
use MiningManager\Console\Commands\UpdateMoonExtractionsCommand;
use MiningManager\Console\Commands\CheckExtractionArrivalsCommand;
use MiningManager\Console\Commands\BackfillExtractionHistoryCommand;
use MiningManager\Console\Commands\BackfillEventRecordsCommand;
use MiningManager\Console\Commands\DetectJackpotsCommand;
use MiningManager\Console\Commands\ScanMetenoxCargoFillCommand;
use MiningManager\Console\Commands\ValidateLifecycleIntegrityCommand;
use MiningManager\Console\Commands\InitializeCommand;
use MiningManager\Console\Commands\CachePriceDataCommand;
use MiningManager\Console\Commands\DiagnosePricesCommand;
use MiningManager\Console\Commands\DiagnoseAffiliationCommand;
use MiningManager\Console\Commands\ResolveCharactersCommand;
use MiningManager\Console\Commands\EveryMinuteCommand;
use MiningManager\Console\Commands\VerifyOreContractsCommand;
use MiningManager\Console\Commands\DiagnoseCharacterCommand;
use MiningManager\Console\Commands\DiagnoseMoonExtractionsCommand;
use MiningManager\Console\Commands\DiagnoseTypeIdsCommand;
use MiningManager\Console\Commands\GenerateTestDataCommand;
use MiningManager\Console\Commands\RecalculateExtractionValuesCommand;
use MiningManager\Console\Commands\ArchiveOldExtractionsCommand;
use MiningManager\Console\Commands\BackfillExtractionNotificationsCommand;
use MiningManager\Console\Commands\DetectMoonTheftCommand;
use MiningManager\Console\Commands\MonitorActiveTheftsCommand;
use MiningManager\Console\Commands\FinalizeMonthCommand;
use MiningManager\Console\Commands\UpdateLedgerPricesCommand;
use MiningManager\Console\Commands\UpdateDailySummariesCommand;
use MiningManager\Console\Commands\ImportCharacterMiningCommand;
use MiningManager\Console\Commands\GenerateTaxCodesCommand;
use MiningManager\Console\Commands\BackupDataCommand;
use MiningManager\Console\Commands\RestoreDataCommand;
use MiningManager\Database\Seeders\ScheduleSeeder;
use Illuminate\Support\Facades\Event;

class MiningManagerServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Check if routes are cached before loading
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
        
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang/', 'mining-manager');
        $this->loadViewsFrom(__DIR__ . '/Resources/views/', 'mining-manager');

        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations/');

        // Reads the corporation's figures: directors, admins, and anyone given
        // "See all data (read-only)". Used by routes and views as one ability.
        \Illuminate\Support\Facades\Gate::define(
            \MiningManager\Support\Access::READS,
            fn ($user) => \MiningManager\Support\Access::readsCorporation($user)
        );

        // Register Blade directives for consistent formatting
        \Illuminate\Support\Facades\Blade::directive('isk', function ($expression) {
            return "<?php
                \$__isk_val = (float)($expression);
                if (\$__isk_val >= 1000000000) {
                    echo number_format(\$__isk_val / 1000000000, 2) . 'B ISK';
                } elseif (\$__isk_val >= 1000000) {
                    echo number_format(\$__isk_val / 1000000, 2) . 'M ISK';
                } else {
                    echo number_format(\$__isk_val, 0) . ' ISK';
                }
            ?>";
        });

        // Standard date format: "Jan 15, 2026 14:30"
        \Illuminate\Support\Facades\Blade::directive('miningDate', function ($expression) {
            return "<?php echo ($expression) ? \Carbon\Carbon::parse($expression)->format('M d, Y H:i') : '-'; ?>";
        });

        // Short date format: "Jan 15, 2026"
        \Illuminate\Support\Facades\Blade::directive('miningDateShort', function ($expression) {
            return "<?php echo ($expression) ? \Carbon\Carbon::parse($expression)->format('M d, Y') : '-'; ?>";
        });

        // Decide whether the tax section shows a Balances tab. Done here rather
        // than in each controller because the tab lives in a partial that every
        // tax page includes, and threading one boolean through six actions to
        // hide one link is not worth it.
        $this->registerBalancesTabComposer();

        // Register event listeners
        $this->registerEventListeners();


        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessMiningLedgerCommand::class,
                ReconcilePersonalMiningCommand::class,
                BackfillOreTypeFlagsCommand::class,
                CalculateMonthlyTaxesCommand::class,
                CalculateMonthlyStatisticsCommand::class,
                GenerateTaxInvoicesCommand::class,
                UpdateMiningEventsCommand::class,
                GenerateReportsCommand::class,
                VerifyWalletPaymentsCommand::class,
                SendOutstandingDigestCommand::class,
                UpdateMoonExtractionsCommand::class,
                CheckExtractionArrivalsCommand::class,
                DetectJackpotsCommand::class,
                ScanMetenoxCargoFillCommand::class,
                ValidateLifecycleIntegrityCommand::class,
                CachePriceDataCommand::class,
                DiagnosePricesCommand::class,
                DiagnoseAffiliationCommand::class,
                ResolveCharactersCommand::class,
                EveryMinuteCommand::class,
                VerifyOreContractsCommand::class,
                DiagnoseCharacterCommand::class,
                DiagnoseMoonExtractionsCommand::class,
                DiagnoseTypeIdsCommand::class,
                GenerateTestDataCommand::class,
                RecalculateExtractionValuesCommand::class,
                ArchiveOldExtractionsCommand::class,
                BackfillExtractionNotificationsCommand::class,
                BackfillExtractionHistoryCommand::class,
                BackfillEventRecordsCommand::class,
                DetectMoonTheftCommand::class,
                MonitorActiveTheftsCommand::class,
                FinalizeMonthCommand::class,
                UpdateLedgerPricesCommand::class,
                UpdateDailySummariesCommand::class,
                ImportCharacterMiningCommand::class,
                GenerateTaxCodesCommand::class,
                InitializeCommand::class,
                BackupDataCommand::class,
                RestoreDataCommand::class,
            ]);
        }

        // Add publications
        $this->add_publications();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register sidebar configuration (SeAT 5.x method)
        $this->mergeConfigFrom(
            __DIR__ . '/Config/Menu/package.sidebar.php',
            'package.sidebar'
        );

        // Register permissions - FIXED: Use correct file in Permissions subfolder
        // SeAT v5 expects permissions to be in /Config/Permissions/ folder
        $this->registerPermissions(
            __DIR__ . '/Config/Permissions/mining-manager.permissions.php',
            'mining-manager'
        );

        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/Config/mining-manager.config.php',
            'mining-manager'
        );

        // Register service singletons for consistent state across the request lifecycle
        // SettingsManagerService holds activeCorporationId state, so must be singleton
        $this->app->singleton(
            \MiningManager\Services\Configuration\SettingsManagerService::class
        );

        // Shared so the controller, the wallet service and the tax calculator
        // all talk to the same allocator, and therefore the same corporation
        // context, within a request.
        $this->app->singleton(
            \MiningManager\Services\Tax\PaymentAllocationService::class
        );

        // Shared so the in-progress notice on a page sees the characters the
        // controllers asked for while building it.
        $this->app->singleton(
            \MiningManager\Services\Character\AffiliationResolutionService::class
        );

        // Names one request or one queued job has looked up, so a page names
        // everything it shows in one batch, and a long-running worker never
        // carries yesterday's names into today's jobs.
        $this->app->scoped(
            \MiningManager\Services\Character\CharacterNames::class
        );

        // Who mines for a corporation, worked out once a request.
        $this->app->scoped(
            \MiningManager\Services\Character\CorporationMembers::class
        );

        $this->app->singleton(
            \MiningManager\Services\Pricing\PriceProviderService::class
        );

        $this->app->singleton(
            \MiningManager\Services\Pricing\MarketDataService::class
        );

        $this->app->singleton(
            \MiningManager\Services\Pricing\OreValuationService::class
        );

        // Add database seeders
        $this->add_database_seeders();
    }

    /**
     * Show the Balances tab only when it would have something to say.
     *
     * Either someone is already holding a balance, or upfront payments are
     * switched on and members could start creating one. On a fresh install with
     * neither, an empty tab just looks broken.
     *
     * The visibility query is cached briefly: this runs on every tax page load,
     * and the answer changes rarely.
     */
    private function registerBalancesTabComposer(): void
    {
        \Illuminate\Support\Facades\View::composer(
            'mining-manager::taxes.partials.tab-navigation',
            function ($view) {
                $visible = false;

                try {
                    $visible = \Illuminate\Support\Facades\Cache::remember(
                        'mining_manager_balances_tab_visible',
                        300,
                        function () {
                            if (\MiningManager\Models\PaymentCredit::where('remaining', '>', 0)->exists()) {
                                return true;
                            }

                            $settings = app(\MiningManager\Services\Configuration\SettingsManagerService::class);

                            return (bool) ($settings->getFeatureFlags()['enable_upfront_payments'] ?? false);
                        }
                    );
                } catch (\Exception $e) {
                    // Before migrations have run the table does not exist yet.
                    // A missing tab is a better failure than a broken tax page.
                    $visible = false;
                }

                $view->with('balancesTabVisible', $visible);
            }
        );
    }

    /**
     * Hooks into SeAT's own jobs and events.
     *
     * Nothing is registered here any more. The hooks this used to hold were
     * bound to names SeAT never uses, so none of them ever ran, and a scheduled
     * command covers each one. The notes stay so they do not come back as they
     * were.
     *
     * @return void
     */
    private function registerEventListeners()
    {
        // Personal mining is imported by mining-manager:import-character-mining
        // on its schedule. There used to be a Queue::after hook here that queued
        // an import each time SeAT finished a character's mining job, but it
        // matched Seat\Eveapi\Jobs\Character\Industry\Mining and the job is
        // Seat\Eveapi\Jobs\Industry\Character\Mining, so it never fired once.
        // Removed rather than repointed. It asked for seven days, and the
        // scheduled run keeps to two so that mining which has already been
        // billed, and its daily summaries, stay as they were. Every import run
        // also takes the same lock, so one queued per character would mostly
        // skip and could make the scheduled run skip too.

        // Tax payments are matched by mining-manager:verify-payments on its
        // schedule. There used to be a listener bound to
        // Seat\Eveapi\Events\CharacterWalletJournalUpdated here, but SeAT has
        // no such event, so it never fired once. It also read the character
        // wallet journal, which is the wrong side of a donation. Removed
        // rather than repointed: the corp journal is what the scheduled run
        // reads, and there is no SeAT event for that either.
    }

    /**
     * Add content which must be published.
     */
    private function add_publications()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/Config/mining-manager.config.php' => config_path('mining-manager.php'),
        ], ['config', 'seat']);
        
        // Publish assets
        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/mining-manager'),
        ], ['public', 'seat']);
    }

    /**
     * Register database seeders
     */
    private function add_database_seeders()
    {
        $this->registerDatabaseSeeders([
            ScheduleSeeder::class,
        ]);
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Mining Manager';
    }

    /**
     * Get the plugin repository URL.
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/Bahu90/EVE-Mining-Manager';
    }

    /**
     * Get the packagist package name.
     *
     * @return string
     */
    public function getPackagistPackageName(): string
    {
        return 'mining-manager';
    }

    /**
     * Get the packagist vendor name.
     *
     * @return string
     */
    public function getPackagistVendorName(): string
    {
        return 'bahu90';
    }
}
