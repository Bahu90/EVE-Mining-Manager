<?php

namespace MiningManager\Support;

/**
 * Short figures as the corporation's pages write them: "76.98 B",
 * "325.55 M", "749 K", "0".
 */
final class IskFormat
{
    private const STEPS = [
        1e12 => 'T',
        1e9 => 'B',
        1e6 => 'M',
        1e3 => 'K',
    ];

    public static function short($value, int $decimals = 2): string
    {
        $value = (float) $value;
        $sign = $value < 0 ? '-' : '';
        $abs = abs($value);

        $steps = array_reverse(self::STEPS, true); // K, M, B, T
        $sizes = array_keys($steps);
        $chosen = null;

        foreach ($sizes as $i => $size) {
            if ($abs >= $size) {
                $chosen = $i;
            }
        }

        // 999.999 M would print as "1000.00 M"; it is written as the next
        // step up ("1.00 B") instead. Below 1 K a value can round up to 1 K.
        if ($chosen === null && round($abs) >= 1000) {
            $chosen = 0;
        }

        if ($chosen === null) {
            return $sign . number_format($abs, 0, '.', '');
        }

        $size = $sizes[$chosen];
        $places = $steps[$size] === 'K' ? 0 : $decimals;

        if (round($abs / $size, $places) >= 1000 && isset($sizes[$chosen + 1])) {
            $chosen++;
            $size = $sizes[$chosen];
            $places = $decimals;
        }

        return $sign . number_format($abs / $size, $places, '.', '') . ' ' . $steps[$size];
    }
}
