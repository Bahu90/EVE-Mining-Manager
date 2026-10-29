<?php

/*
|--------------------------------------------------------------------------
| Mining Manager - Default Configuration
|--------------------------------------------------------------------------
|
| These are default values that can be overridden via the Settings UI.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        // Corporation ID (can be set via settings page)
        'corporation_id' => null,

        // Ore valuation method: 'ore_price' or 'mineral_price'
        'ore_valuation_method' => 'mineral_price',

        // Price provider: 'eve_market' or 'janice'
        'price_provider' => 'eve_market',

        // API key for price provider (if using Janice)
        'price_provider_api_key' => env('MINING_MANAGER_JANICE_API_KEY', ''),

        // Price modifier percentage (adjusts all prices by this %)
        'price_modifier' => 0.0,

        // Market hub region ID (10000002 = The Forge/Jita)
        'default_region_id' => 10000002,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */
    'pricing' => [
        // Price type: 'sell', 'buy', 'average'
        'price_type' => 'sell',

        // Cache price data duration (minutes)
        'cache_duration' => 240,

        // Which value the Extraction Simulator and Find Moons lead with. Raw
        // moon ore barely trades, so its market price says less about a moon
        // than what the ore reprocesses into.
        'use_refined_value' => true,

        // Refining efficiency percentage (for refined value calculations)
        'refining_efficiency' => 87.5,

        // Fallback to Jita prices if regional data unavailable
        'fallback_to_jita' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Moon Extractions
    |--------------------------------------------------------------------------
    */
    'moon' => [
        // Estimated chunk size in cubic meters (m3)
        // Moon chunks typically range from 100,000 to 200,000 m3
        // Adjust based on your corporation's moon sizes
        'estimated_chunk_size' => env('MOON_CHUNK_SIZE', 150000),

        // Auto-calculate extraction values
        'auto_calculate_values' => true,

        // Include unscanned moons in reports
        'show_unscanned_moons' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Enable or disable major features of the Mining Manager plugin.
    */
    'features' => [
        // Core features
        'enable_ledger_tracking' => true,
        'enable_analytics' => true,
        'enable_reports' => true,

        // Events
        'enable_events' => true,
        'allow_event_creation' => true,

        // Moon mining
        'enable_moon_tracking' => true,

        // Permissions & access
        'allow_public_stats' => false,
        'allow_member_leaderboard' => true,
        'show_character_names' => true,
        'allow_export_data' => true,

        // Automation
        'auto_process_ledger' => true,

        // Data retention
        'ledger_retention_days' => 365,
        'auto_cleanup_old_data' => false,
    ],

];
