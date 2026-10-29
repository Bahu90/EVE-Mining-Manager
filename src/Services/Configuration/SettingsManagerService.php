<?php

namespace MiningManager\Services\Configuration;

use MiningManager\Services\Security\SecretValue;
use MiningManager\Services\Security\WebhookUrlPolicy;
use MiningManager\Models\Setting;
use MiningManager\Models\WebhookConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SettingsManagerService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 60;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'mining_manager_settings_';

    /**
     * Active corporation ID for settings context
     *
     * @var int|null
     */
    protected $activeCorporationId = null;

    /**
     * Set the active corporation context
     *
     * @deprecated Use getSettingForCorporation() instead to avoid singleton state mutation.
     * @param int|null $corporationId
     * @return self
     */
    public function setActiveCorporation(?int $corporationId): self
    {
        $this->activeCorporationId = $corporationId;
        return $this;
    }

    /**
     * Get the active corporation ID
     *
     * @return int|null
     */
    public function getActiveCorporation(): ?int
    {
        return $this->activeCorporationId;
    }

    /**
     * Check if a corporation has custom settings
     *
     * @param int $corporationId
     * @return bool
     */
    public function corporationHasCustomSettings(int $corporationId): bool
    {
        return Setting::where('corporation_id', $corporationId)->exists();
    }

    /**
     * Get a setting value with fallback to config
     * Now supports corporation-specific settings
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . ($this->activeCorporationId ?? 'global') . '_' . $key;

        return $this->openSecret($key, Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $default) {
            $setting = Setting::getValue($key, null, $this->activeCorporationId);

            if ($setting !== null) {
                return $setting;
            }

            // Fallback to config
            $configKey = 'mining-manager.' . $key;
            return config($configKey, $default);
        }));
    }

    /**
     * Get a setting for a specific corporation without mutating the active context.
     *
     * @param string $key
     * @param int|null $corporationId
     * @param mixed $default
     * @return mixed
     */
    public function getSettingForCorporation(string $key, ?int $corporationId, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . ($corporationId ?? 'global') . '_' . $key;

        return $this->openSecret($key, Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $default, $corporationId) {
            $setting = Setting::getValue($key, null, $corporationId);

            if ($setting !== null) {
                return $setting;
            }

            $configKey = 'mining-manager.' . $key;
            return config($configKey, $default);
        }));
    }

    /**
     * Update or create a setting that belongs to the install rather than to a
     * corporation.
     *
     * Several commands set an active corporation on this service and leave it
     * set for the rest of their run. Anything written in that window lands on
     * that corporation, which is right for a per-corporation setting and wrong for a fact
     * about the install, such as whether the price provider is answering:
     * those end up scattered across a row per corporation, each with its own
     * half of the story.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     */
    public function updateGlobalSetting(string $key, $value, ?string $type = null)
    {
        $previous = $this->activeCorporationId;

        try {
            $this->activeCorporationId = null;
            $this->updateSetting($key, $value, $type);
        } finally {
            $this->activeCorporationId = $previous;
        }
    }

    /**
     * Update or create a setting
     * Now supports corporation-specific settings
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     */
    /**
     * Settings that hold credentials. They are stored encrypted and read back
     * decrypted (cache entries keep the encrypted form).
     */
    public const SECRET_KEYS = ['notifications.slack_webhook_url'];

    private function openSecret(string $key, $value)
    {
        if (in_array($key, self::SECRET_KEYS, true) && is_string($value)) {
            return SecretValue::open($value) ?? '';
        }

        return $value;
    }

    public function updateSetting(string $key, $value, ?string $type = null)
    {
        if ($type === null) {
            $type = $this->detectType($value);
        }

        // Clear cache BEFORE writing — if another process reads between our
        // DB write and a post-write cache clear, it would populate the cache
        // with the old value (a second's worth of stale reads). Clearing
        // before write means the worst case is a cache miss that re-reads
        // fresh from DB.
        $globalCacheKey = self::CACHE_PREFIX . 'global_' . $key;
        $corpCacheKey = $this->activeCorporationId
            ? self::CACHE_PREFIX . $this->activeCorporationId . '_' . $key
            : null;

        Cache::forget($globalCacheKey);
        if ($corpCacheKey) {
            Cache::forget($corpCacheKey);
        }

        if (in_array($key, self::SECRET_KEYS, true) && is_string($value)) {
            $value = SecretValue::seal($value);
        }

        Setting::setValue($key, $value, $type, $this->activeCorporationId);

        // Clear again AFTER the write as belt-and-suspenders — covers the
        // (unlikely) case where another process read+cached during the
        // brief window between pre-clear and write. Idempotent, negligible
        // cost for a setting update.
        Cache::forget($globalCacheKey);
        if ($corpCacheKey) {
            Cache::forget($corpCacheKey);
        }

        // Demoted from info to debug — bulk saves (saveNotificationSettings,
        // saveGeneralSettings) emit dozens of nearly-identical lines per
        // request. The controller-level batch-complete log line is what
        // operators actually want to see at info; per-key writes are
        // diagnostic-only.
        $corpContext = $this->activeCorporationId ? " (Corp ID: {$this->activeCorporationId})" : " (Global)";
        Log::debug("Mining Manager: Setting updated - {$key}{$corpContext}");
    }

    /**
     * Get general settings
     * UPDATED: Now includes corporation_id and all settings are database-first with config fallback
     *
     * @return array
     */
    public function getGeneralSettings(): array
    {
        return [
            // Corporation Settings (from database, config as fallback)
            'corporation_id' => $this->getSetting('general.corporation_id', config('mining-manager.general.corporation_id')),
            'corporation_name' => $this->getSetting('general.corporation_name', ''),
            'corporation_ticker' => $this->getSetting('general.corporation_ticker', ''),
            // The corporation that owns the moons and refineries. Moon
            // notifications and ledger tracking are scoped to this corp,
            // regardless of ore source (moon, belt, ice, gas). Webhook
            // notifications for moon events are also filtered to only reach
            // webhooks tied to this corp (see WebhookDispatchTrait::getMoonOwnerScopedWebhooks).
            // Other directors' corps on the same SeAT install are excluded from these notifications.
            'moon_owner_corporation_id' => $this->getSetting('general.moon_owner_corporation_id', config('mining-manager.general.moon_owner_corporation_id')),

            // Ore Refining Settings (unified: reads from pricing.refining_efficiency)
            'ore_refining_rate' => $this->getSetting('pricing.refining_efficiency', config('mining-manager.pricing.refining_efficiency', 87.5)),
            'ore_valuation_method' => $this->getSetting('general.ore_valuation_method', config('mining-manager.general.ore_valuation_method', 'mineral_price')),
            
            // Price Provider Settings
            'price_provider' => $this->getSetting('price_provider', config('mining-manager.general.price_provider', 'eve_market')),
            'default_region_id' => $this->getSetting('general.default_region_id', config('mining-manager.general.default_region_id', 10000002)),
            'price_modifier' => $this->getSetting('general.price_modifier', config('mining-manager.general.price_modifier', 0.0)),
            
            // Time Settings - Always UTC for consistency with EVE Online
            // These are hard-coded and not configurable to prevent issues with:
            // - Corporation mining ledger timestamps
            // - Month boundaries
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',

            // Display Settings
            'currency_decimals' => $this->getSetting('general.currency_decimals', 2),
            'items_per_page' => $this->getSetting('general.items_per_page', 25),
            'compact_mode' => $this->getSetting('general.compact_mode', false),
            'show_character_portraits' => $this->getSetting('general.show_character_portraits', true),
        ];
    }

    /**
     * Get corporation ID (convenience method)
     * Used throughout the plugin for data filtering.
     *
     * @deprecated Use getMoonOwnerCorporationId() instead. This alias is kept
     *             for backward compatibility but delegates to the canonical
     *             accessor. The legacy `general.corporation_id` setting is no
     *             longer consulted.
     * @return int|null
     */
    public function getCorporationId(): ?int
    {
        return $this->getMoonOwnerCorporationId();
    }

    /**
     * Update general settings
     *
     * @param array $settings
     * @return void
     */
    public function updateGeneralSettings(array $settings)
    {
        DB::beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                // Moon Owner Corporation is ALWAYS global — single source of truth
                // for which structures provide moon mining data, independent of
                // corporation context.
                if ($key === 'moon_owner_corporation_id') {
                    $savedContext = $this->activeCorporationId;
                    $this->activeCorporationId = null; // Force global save
                    $this->updateSetting('general.moon_owner_corporation_id', $value, 'integer');
                    $this->activeCorporationId = $savedContext;
                } else {
                    $this->updateSetting('general.' . $key, $value);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * The Moon Owner Corporation: the corporation that owns the moons and
     * refineries this install tracks. Stored as general.moon_owner_corporation_id.
     *
     * @return int|null
     */
    public function getMoonOwnerCorporationId(): ?int
    {
        $value = $this->getSetting('general.moon_owner_corporation_id');
        return $value ? (int) $value : null;
    }

    /**
     * Get pricing settings
     *
     * @return array
     */
    public function getPricingSettings(): array
    {
        return [
            // Price provider (checks settings first, falls back to ENV)
            'price_provider' => $this->getSetting('price_provider', config('mining-manager.general.price_provider', 'seat')),
            'price_type' => $this->getSetting('pricing.price_type', 'sell'),
            'cache_duration' => $this->getSetting('pricing.cache_duration', 240),
            'fallback_to_jita' => $this->getSetting('pricing.fallback_to_jita', true),

            // Asked when the price provider fails outright; 'none' keeps the
            // cached prices instead.
            'fallback_provider' => $this->getSetting('pricing.fallback_provider', 'fuzzwork'),

            // Janice settings (checks settings first, then falls back to ENV)
            'janice_api_key' => $this->getSetting('janice_api_key')
                ?: config('mining-manager.general.price_provider_api_key', ''),
            'janice_market' => $this->getSetting('janice_market', 'jita'),
            'janice_price_method' => $this->getSetting('janice_price_method', 'buy'),

            // Refining settings
            'use_refined_value' => $this->getSetting('pricing.use_refined_value', config('mining-manager.pricing.use_refined_value', true)),
            'refining_efficiency' => $this->getSetting('pricing.refining_efficiency', 87.5),
        ];
    }

    /**
     * Update pricing settings
     *
     * @param array $settings
     * @return void
     */
    public function updatePricingSettings(array $settings)
    {
        DB::beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->updateSetting('pricing.' . $key, $value);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get event settings
     *
     * @return array
     */
    public function getEventSettings(): array
    {
        return config('mining-manager.events', []);
    }

    /**
     * Get moon settings
     *
     * @return array
     */
    public function getMoonSettings(): array
    {
        return [
            'estimated_chunk_size' => $this->getSetting('moon.estimated_chunk_size', config('mining-manager.moon.estimated_chunk_size', 150000)),
            'auto_calculate_values' => $this->getSetting('moon.auto_calculate_values', config('mining-manager.moon.auto_calculate_values', true)),
            'show_unscanned_moons' => $this->getSetting('moon.show_unscanned_moons', config('mining-manager.moon.show_unscanned_moons', true)),
        ];
    }

    /**
     * Get report settings
     *
     * @return array
     */
    public function getReportSettings(): array
    {
        return config('mining-manager.reports', []);
    }

    /**
     * The types with a card on Settings > Notifications, which is where a
     * type's master switch and role ping are set. Saving that page writes
     * these types only. A type with no card keeps what is stored for it, or
     * the defaults: on, no role ping.
     */
    public const NOTIFICATION_TYPES = [
        // Events
        'event_created', 'event_started', 'event_completed',
        // Moon
        'moon_ready', 'jackpot_detected', 'moon_chunk_unstable',
        // Planner — extraction lifecycle coordination
        'extraction_started', 'next_extraction_planned', 'schedule_mismatch',
        'refinery_gone', 'extraction_cancelled', 'moon_not_rescheduled', 'schedule_needs_filling',
        // Metenox — bay-full warning
        'metenox_cargo_full',
        // Plugin health
        'price_provider', 'moon_scan_missing',
        // Reports
        'report_generated',
    ];

    /**
     * The types with a box in the Slack list on the same page. A list of its
     * own, as a type can have a Slack box without a card or the other way
     * round, and a box missing from its list would save as off.
     */
    public const SLACK_NOTIFICATION_TYPES = [
        // Events
        'event_created', 'event_started', 'event_completed',
        // Moon
        'moon_ready', 'jackpot_detected', 'moon_chunk_unstable', 'extraction_started',
        'metenox_cargo_full',
        // Planner
        'next_extraction_planned', 'schedule_mismatch', 'refinery_gone', 'extraction_cancelled',
        'moon_not_rescheduled', 'schedule_needs_filling',
        // Plugin health
        'price_provider', 'moon_scan_missing',
        // Reports
        'report_generated',
    ];

    /**
     * Get notification settings
     *
     * @return array
     */
    public function getNotificationSettings(): array
    {
        $allTypes = self::NOTIFICATION_TYPES;

        // Build default enabled_types (all true)
        $defaultEnabled = array_fill_keys($allTypes, true);

        return [
            // Global per-type toggles (master switches)
            'enabled_types' => $this->getSetting('notifications.enabled_types', $defaultEnabled),

            // Per-type settings (role ping, user ping, show amount)
            'type_settings' => $this->getSetting('notifications.type_settings', []),

            // EVE Mail
            'evemail_enabled' => (bool) $this->getSetting('notifications.evemail_enabled', false),
            'evemail_sender_character_id' => $this->getSetting('notifications.evemail_sender_character_id', null),
            'evemail_sender_character_override' => $this->getSetting('notifications.evemail_sender_character_override', null),
            'evemail_types' => $this->getSetting('notifications.evemail_types', $defaultEnabled),

            // Slack
            'slack_enabled' => (bool) $this->getSetting('notifications.slack_enabled', false),
            'slack_webhook_url' => $this->getSetting('notifications.slack_webhook_url', ''),
            'slack_types' => $this->getSetting('notifications.slack_types', $defaultEnabled),
            // Legacy global Slack health metrics. Per-webhook rows in
            // webhook_configurations track success/failure/last_error on
            // the model itself; the legacy global path (single URL stored
            // in `notifications.slack_webhook_url`) had no equivalent
            // until the M7 fix wired up persistent counters via
            // recordLegacySlackSuccess / recordLegacySlackFailure in
            // NotificationService::sendViaSlack.
            'slack_legacy_success_count' => (int) $this->getSetting('notifications.slack_legacy_success_count', 0),
            'slack_legacy_failure_count' => (int) $this->getSetting('notifications.slack_legacy_failure_count', 0),
            'slack_legacy_last_success_at' => $this->getSetting('notifications.slack_legacy_last_success_at', null),
            'slack_legacy_last_failure_at' => $this->getSetting('notifications.slack_legacy_last_failure_at', null),
            'slack_legacy_last_error' => $this->getSetting('notifications.slack_legacy_last_error', ''),

            // Legacy discord pinging (kept for backward compat during transition)
            'discord_pinging_enabled' => (bool) $this->getSetting('notifications.discord_pinging_enabled', false),
            'discord_ping_show_amount' => (bool) $this->getSetting('notifications.discord_ping_show_amount', true),

            // Metenox cargo-full notification threshold (default 85%, clamped
            // [50, 99] both client-side via the UI input + server-side in the
            // scanner). Read here so the settings view can prefill the input.
            'metenox_cargo_full_threshold_pct' => (float) $this->getSetting('notifications.metenox_cargo_full_threshold_pct', 85),

            // Moon Extraction Planner — minimum gap (hours) between two chunk
            // arrivals before the planner warns about clustering.
            'min_extraction_gap_hours' => (int) $this->getSetting('notifications.min_extraction_gap_hours', 24),

            // Moon Extraction Planner: how close two pulls on one refinery may
            // be before the planner warns, in hours. The form shows days.
            'min_refinery_spacing_hours' => (int) $this->getSetting(
                'notifications.min_refinery_spacing_hours',
                \MiningManager\Services\Moon\MoonPlannerService::DEFAULT_REFINERY_SPACING_HOURS
            ),

            // Moon Not Rescheduled: idle hours before the reminder, and whether
            // it repeats every that many hours until an extraction starts.
            'moon_not_rescheduled_hours' => (int) $this->getSetting('notifications.moon_not_rescheduled_hours', 48),
            'moon_not_rescheduled_repeat' => (bool) $this->getSetting('notifications.moon_not_rescheduled_repeat', true),

            // Moons Need Planning: pulls each refinery should have planned
            // ahead, and hours between lists while some fall short.
            'planned_ahead_target' => (int) $this->getSetting('notifications.planned_ahead_target', 1),
            'schedule_needs_filling_hours' => (int) $this->getSetting('notifications.schedule_needs_filling_hours', 24),

            // Moon Scan Missing: list every moon still missing a scan again
            // once a day, on top of the message for each new reason.
            'moon_scan_missing_daily' => (bool) $this->getSetting('notifications.moon_scan_missing_daily', false),

            // seat-connector availability (runtime)
            'seat_connector_available' => \Illuminate\Support\Facades\Schema::hasTable('seat_connector_users'),
        ];
    }

    /**
     * Get per-type notification settings for a specific type
     *
     * @param string $type Notification type key (e.g., 'moon_ready')
     * @return array ['ping_role' => bool, 'role_id' => string|null, 'ping_user' => bool, 'show_amount' => bool]
     */
    public function getTypeNotificationSettings(string $type): array
    {
        $allTypeSettings = $this->getSetting('notifications.type_settings', []);
        $typeSettings = $allTypeSettings[$type] ?? [];

        return [
            'ping_role' => (bool) ($typeSettings['ping_role'] ?? false),
            'role_id' => $typeSettings['role_id'] ?? null,
            'ping_user' => (bool) ($typeSettings['ping_user'] ?? false),
            'show_amount' => (bool) ($typeSettings['show_amount'] ?? true),
            // Only the corporations picked on the card; none picked means all.
            'corporations' => array_values(array_filter(array_map('intval', (array) ($typeSettings['corporations'] ?? [])))),
        ];
    }

    /**
     * Update notification settings
     *
     * @param array $settings
     * @return void
     */
    public function updateNotificationSettings(array $settings): void
    {
        DB::beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                $this->updateSetting('notifications.' . $key, $value);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get characters that have the esi-mail.send_mail.v1 scope
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMailScopeCharacters(): \Illuminate\Support\Collection
    {
        return DB::table('refresh_tokens')
            ->join('character_infos', 'refresh_tokens.character_id', '=', 'character_infos.character_id')
            ->where('refresh_tokens.scopes', 'LIKE', '%esi-mail.send_mail.v1%')
            ->select('refresh_tokens.character_id', 'character_infos.name')
            ->orderBy('character_infos.name')
            ->get();
    }

    /**
     * Get all characters with refresh tokens (regardless of scopes).
     * Includes a has_mail_scope flag to indicate if the character can send EVE mail.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllTokenCharacters(): \Illuminate\Support\Collection
    {
        return DB::table('refresh_tokens')
            ->join('character_infos', 'refresh_tokens.character_id', '=', 'character_infos.character_id')
            ->select(
                'refresh_tokens.character_id',
                'character_infos.name',
                DB::raw("CASE WHEN refresh_tokens.scopes LIKE '%esi-mail.send_mail.v1%' THEN 1 ELSE 0 END as has_mail_scope")
            )
            ->orderBy('character_infos.name')
            ->get();
    }

    /**
     * Get dashboard settings
     *
     * @return array
     */
    public function getDashboardSettings(): array
    {
        $configDefaults = config('mining-manager.dashboard', []);

        // Install-wide: read the way the dashboard reads them, whatever
        // corporation the settings page is set to.
        return array_merge($configDefaults, [
            'dashboard_leaderboard_corporation_filter' => $this->getSettingForCorporation('dashboard_leaderboard_corporation_filter', null, 'all'),
            'dashboard_leaderboard_corporation_ids' => $this->getSettingForCorporation('dashboard_leaderboard_corporation_ids', null, '[]'),
            'display_theme' => \MiningManager\Support\Look::current(),
        ]);
    }

    /**
     * Get feature flags
     *
     * @return array
     */
    public function getFeatureFlags(): array
    {
        return [
            // Core features
            'enable_ledger_tracking' => (bool) $this->getSetting('features.enable_ledger_tracking', true),
            'enable_analytics' => (bool) $this->getSetting('features.enable_analytics', true),
            'enable_reports' => (bool) $this->getSetting('features.enable_reports', true),

            // Events
            'enable_events' => (bool) $this->getSetting('features.enable_events', true),
            'allow_event_creation' => (bool) $this->getSetting('features.allow_event_creation', true),

            // Moon mining
            'enable_moon_tracking' => (bool) $this->getSetting('features.enable_moon_tracking', true),

            // Permissions & access
            'allow_public_stats' => (bool) $this->getSetting('features.allow_public_stats', false),
            'allow_member_leaderboard' => (bool) $this->getSetting('features.allow_member_leaderboard', true),
            'show_character_names' => (bool) $this->getSetting('features.show_character_names', true),
            'allow_export_data' => (bool) $this->getSetting('features.allow_export_data', true),

            // Automation
            'auto_process_ledger' => (bool) $this->getSetting('features.auto_process_ledger', true),

            // Data retention
            'ledger_retention_days' => (int) $this->getSetting('features.ledger_retention_days', 365),
            'auto_cleanup_old_data' => (bool) $this->getSetting('features.auto_cleanup_old_data', false),
        ];
    }

    /**
     * Update feature flags
     *
     * @param array $features
     * @return void
     */
    public function updateFeatureFlags(array $features)
    {
        DB::beginTransaction();

        try {
            foreach ($features as $key => $value) {
                $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');

                $this->updateSetting("features.{$key}", $value, $type);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reset all settings to config defaults
     *
     * @return void
     */
    public function resetToDefaults()
    {
        DB::beginTransaction();

        try {
            // Get all setting keys before truncating so we can clear their cache entries
            $settingKeys = Setting::pluck('key')->toArray();
            $corporationIds = Setting::whereNotNull('corporation_id')
                ->distinct()
                ->pluck('corporation_id')
                ->toArray();

            Setting::truncate();

            // Clear only mining-manager cache keys (not the entire application cache)
            foreach ($settingKeys as $key) {
                Cache::forget(self::CACHE_PREFIX . 'global_' . $key);
                foreach ($corporationIds as $corpId) {
                    Cache::forget(self::CACHE_PREFIX . $corpId . '_' . $key);
                }
            }

            // Also try tag-based flush if cache driver supports it
            try {
                Cache::tags(['mining-manager'])->flush();
            } catch (\Exception $cacheException) {
                // File/database cache driver doesn't support tags - acceptable
            }

            Log::info('Mining Manager: All settings reset to defaults');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Export all settings as array
     *
     * @return array
     */
    /**
     * Export settings for moving a configuration between installs.
     *
     * Every row carries its own corporation_id. A flat key => value map cannot
     * represent this data: most keys exist several times over, once globally and
     * once per configured corporation, and flattening them silently keeps
     * whichever row the database happened to return last.
     *
     * Webhooks are opt-in because webhook_url is a credential. Anyone holding
     * the exported file can post to those channels, so putting them in by
     * default would turn a settings backup into a secret people email around.
     *
     * @param bool $includeWebhooks
     * @return array
     */
    public function exportSettings(bool $includeWebhooks = false): array
    {
        $settings = Setting::query()
            ->orderBy('corporation_id')
            ->orderBy('key')
            ->get()
            ->filter(function ($setting) use ($includeWebhooks) {
                // The Slack URL is a credential like the webhooks, so it only
                // travels when the admin asked for webhooks to be included.
                return $includeWebhooks || !in_array($setting->key, self::SECRET_KEYS, true);
            })
            ->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => in_array($setting->key, self::SECRET_KEYS, true)
                        ? SecretValue::open($setting->value)
                        : $setting->value,
                    'type' => $setting->type,
                    'corporation_id' => $setting->corporation_id,
                    'description' => $setting->description,
                ];
            })
            ->values()
            ->all();

        $export = [
            'exported_at' => now()->toDateTimeString(),
            'version' => config('mining-manager.version', '1.0.0'),
            'format' => 2,
            'settings' => $settings,
        ];

        if ($includeWebhooks) {
            $export['webhooks'] = WebhookConfiguration::query()
                ->orderBy('corporation_id')
                ->orderBy('name')
                ->get()
                ->map(function ($hook) {
                    // The URL is hidden from toArray(), which made exported
                    // webhooks impossible to import. Put it in explicitly,
                    // decrypted, since the admin opted in to exporting it.
                    $row = $hook->toArray();
                    unset($row['webhook_url_hint']);
                    $row['webhook_url'] = $hook->webhook_url;

                    // Delivery statistics belong to the install that produced
                    // them, not to the configuration being moved.
                    foreach ([
                        'id', 'success_count', 'failure_count',
                        'last_success_at', 'last_failure_at', 'last_error',
                        'created_at', 'updated_at',
                    ] as $drop) {
                        unset($row[$drop]);
                    }

                    return $row;
                })
                ->values()
                ->all();
        }

        return $export;
    }

    /**
     * Import a settings export.
     *
     * Understands both shapes: the current one, where settings are rows that
     * each name their own corporation, and the original flat key => value map,
     * which had no corporation information at all and is therefore treated as
     * global. Files produced before this change still import exactly as they
     * did before.
     *
     * Additive. Nothing is deleted, so importing a partial file tops up a
     * configuration rather than replacing it.
     *
     * @param array $data
     * @return array{settings:int,webhooks:int,skipped:int,legacy:bool}
     */
    public function importSettings(array $data): array
    {
        if (!isset($data['settings'])) {
            throw new \Exception('Invalid settings format');
        }

        $rows = $data['settings'];
        $legacy = !array_is_list($rows);

        $imported = 0;
        $skipped = 0;
        $webhooks = 0;
        $savedContext = $this->activeCorporationId;

        DB::beginTransaction();

        try {
            if ($legacy) {
                // Original format: a flat map with no corporation context, so
                // everything lands globally, which is where it came from.
                $this->activeCorporationId = null;

                foreach ($rows as $key => $value) {
                    $this->updateSetting($key, $value);
                    $imported++;
                }
            } else {
                foreach ($rows as $row) {
                    if (!is_array($row) || !isset($row['key'])) {
                        $skipped++;
                        continue;
                    }

                    // Write in the row's own context, not the caller's.
                    $this->activeCorporationId = isset($row['corporation_id'])
                        ? ($row['corporation_id'] === null ? null : (int) $row['corporation_id'])
                        : null;

                    $this->updateSetting(
                        $row['key'],
                        $row['value'] ?? null,
                        $row['type'] ?? null
                    );

                    $imported++;
                }
            }

            if (!empty($data['webhooks']) && is_array($data['webhooks'])) {
                foreach ($data['webhooks'] as $hook) {
                    if (!is_array($hook) || empty($hook['name'])) {
                        $skipped++;
                        continue;
                    }

                    // Same rules as the webhook form: HTTPS to a public host.
                    // An imported file is not trusted more than a typed URL.
                    if (!WebhookUrlPolicy::isAllowed($hook['webhook_url'] ?? null)) {
                        Log::warning('Mining Manager: skipped importing webhook with a refused URL', [
                            'name' => $hook['name'],
                            'reason' => WebhookUrlPolicy::problem($hook['webhook_url'] ?? null),
                        ]);
                        $skipped++;
                        continue;
                    }

                    // Name plus corporation identifies a webhook well enough to
                    // re-import over itself without piling up duplicates.
                    WebhookConfiguration::updateOrCreate(
                        [
                            'name' => $hook['name'],
                            'corporation_id' => $hook['corporation_id'] ?? null,
                        ],
                        $hook
                    );

                    $webhooks++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->activeCorporationId = $savedContext;
            throw $e;
        }

        $this->activeCorporationId = $savedContext;

        // updateSetting() drops each key's own cache entry as it writes. The
        // wider flush (aggregate getters, tab visibility) belongs to the caller,
        // which knows whether it is finishing a request.
        Log::info('Mining Manager: settings imported', [
            'settings' => $imported,
            'webhooks' => $webhooks,
            'skipped' => $skipped,
            'legacy_format' => $legacy,
        ]);

        return [
            'settings' => $imported,
            'webhooks' => $webhooks,
            'skipped' => $skipped,
            'legacy' => $legacy,
        ];
    }

    /**
     * Get all "home" corporation IDs — configured corporations + moon owner corp.
     * Characters belonging to any of these are considered corp members.
     * Characters from any other corporation are considered guest miners.
     *
     * @return array<int>
     */
    public function getHomeCorporationIds(): array
    {
        // Get configured corporations (from "Switch Corporation Context" list)
        $configuredCorpIds = DB::table('mining_manager_settings')
            ->whereNotNull('corporation_id')
            ->distinct()
            ->pluck('corporation_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Also include moon owner corporation (structure/moon holding corp)
        $moonOwnerCorpId = $this->getSetting('general.moon_owner_corporation_id');
        if ($moonOwnerCorpId) {
            $configuredCorpIds[] = (int) $moonOwnerCorpId;
        }

        return array_values(array_unique($configuredCorpIds));
    }

    /**
     * Get all corporations that have settings configured
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllCorporations()
    {
        return DB::table('corporation_infos')
            ->whereIn('corporation_id', function($query) {
                $query->select('corporation_id')
                    ->from('mining_manager_settings')
                    ->whereNotNull('corporation_id')
                    ->distinct();
            })
            ->select('corporation_id', 'name', 'ticker')
            ->orderBy('name')
            ->get();
    }

    /**
     * Test price provider connection
     *
     * @return array
     */
    public function testPriceProviderConnection(): array
    {
        $provider = $this->getSetting('price_provider', 'eve_market');

        // Test connection logic here
        // This is a placeholder - implement actual testing

        return [
            'provider' => $provider,
            'status' => 'connected',
            'latency' => '123ms',
        ];
    }

    /**
     * Cast value to appropriate type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function castValue($value, string $type)
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Detect type of value
     *
     * @param mixed $value
     * @return string
     */
    private function detectType($value): string
    {
        return match(true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
