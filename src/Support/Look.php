<?php

namespace MiningManager\Support;

use MiningManager\Services\Configuration\SettingsManagerService;

/**
 * Which look the plugin's pages use: the EVE-RO look (the corporation's own
 * mining page, default) or the plugin's standard styling. Install-wide,
 * chosen under Settings > Dashboard.
 */
final class Look
{
    public const KEY = 'display.theme';
    public const EVE_RO = 'eve-ro';
    public const STANDARD = 'standard';

    public const ALL = [self::EVE_RO, self::STANDARD];

    private static ?string $current = null;

    public static function current(): string
    {
        if (self::$current !== null) {
            return self::$current;
        }

        try {
            $value = app(SettingsManagerService::class)->getSettingForCorporation(self::KEY, null, self::EVE_RO);
        } catch (\Throwable $e) {
            $value = self::EVE_RO;
        }

        return self::$current = in_array($value, self::ALL, true) ? $value : self::EVE_RO;
    }

    public static function forget(): void
    {
        self::$current = null;
    }
}
