<?php

namespace MiningManager\Support;

use MiningManager\Services\Configuration\SettingsManagerService;

/**
 * Whether tax codes are used (Settings > Tax Rates > Use tax codes).
 *
 * A tax code in an ISK transfer's reason or an ore contract's title tells
 * the plugin which bill it pays. A corporation that does not want them turns
 * them off: no codes are made or shown, ore contracts are matched to the
 * pilot's open bills, and ISK transfers are matched by hand on the Wallet
 * page.
 */
final class TaxCodes
{
    public const SETTING = 'tax_rates.auto_generate_tax_codes';

    public static function enabled(): bool
    {
        try {
            return (bool) app(SettingsManagerService::class)->getSetting(self::SETTING, true);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
