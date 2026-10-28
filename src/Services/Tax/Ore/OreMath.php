<?php

namespace MiningManager\Services\Tax\Ore;

/**
 * The arithmetic of paying tax in ore, kept free of the database so it can
 * be tested on its own.
 *
 * A bill is an ISK amount. Its ore equivalent is worked out from the mining
 * it was calculated from: for every ore, the units it was taxed on at the
 * rate it was taxed at (an R64 ore at the R64 rate, an event-discounted day at
 * its discounted rate), then scaled so the whole list is worth exactly the
 * bill. The scaling carries anything applied on top of the per-ore tax, such
 * as a minimum charge or an adjustment, through to the ore.
 *
 * Paying works the other way: ore a member hands in is valued per unit at
 * what that ore is worth on their bill, so handing in exactly the listed ore
 * pays exactly the bill.
 */
final class OreMath
{
    /**
     * Tax lines from a period's daily summary ore entries.
     *
     * @param iterable<array> $ores entries with type_id, ore_name, quantity,
     *                              total_value and estimated_tax
     * @return array<int, array{type_id:int, name:string, units:float, isk:float, unit_value:float}>
     *         keyed by type_id
     */
    public static function linesFromOres(iterable $ores): array
    {
        $lines = [];

        foreach ($ores as $ore) {
            $typeId = (int) ($ore['type_id'] ?? 0);
            $quantity = (float) ($ore['quantity'] ?? 0);
            $value = (float) ($ore['total_value'] ?? 0);
            $tax = (float) ($ore['estimated_tax'] ?? 0);

            if ($typeId <= 0 || $quantity <= 0 || $value <= 0 || $tax <= 0) {
                continue;
            }

            if (!isset($lines[$typeId])) {
                $lines[$typeId] = [
                    'type_id' => $typeId,
                    'name' => (string) ($ore['ore_name'] ?? "Type {$typeId}"),
                    'units' => 0.0,
                    'isk' => 0.0,
                    'unit_value' => 0.0,
                ];
            }

            // The units this entry was taxed on at its own effective rate.
            $lines[$typeId]['units'] += $quantity * ($tax / $value);
            $lines[$typeId]['isk'] += $tax;
        }

        return self::withUnitValues($lines);
    }

    /**
     * Scale tax lines so they are worth $target ISK in all.
     */
    public static function scaleTo(array $lines, float $target): array
    {
        $total = array_sum(array_column($lines, 'isk'));

        if ($total <= 0 || $target <= 0) {
            return [];
        }

        $factor = $target / $total;

        foreach ($lines as &$line) {
            $line['units'] *= $factor;
            $line['isk'] *= $factor;
        }
        unset($line);

        return self::withUnitValues($lines);
    }

    /**
     * Whole units to hand in, rounded up so a member who hands in exactly the
     * listed ore never falls a fraction of a unit short.
     */
    public static function unitsToHandIn(float $units): int
    {
        return (int) ceil(round($units, 6));
    }

    /**
     * Value ore handed in against a set of unit values.
     *
     * @param array<int, int|float> $items type_id => units handed in (raw type ids)
     * @param array<int, float> $unitValues type_id => ISK per unit, from the bill
     * @param callable|null $marketPrice fn(int $typeId): ?float, for ore the bill
     *                                   does not list; null leaves it unvalued
     * @return array{value: float, lines: array<int, array{type_id:int, units:float, unit_value:?float, value:float, on_bill:bool}>}
     */
    public static function valueHandedIn(array $items, array $unitValues, ?callable $marketPrice = null): array
    {
        $out = ['value' => 0.0, 'lines' => []];

        foreach ($items as $typeId => $units) {
            $typeId = (int) $typeId;
            $units = (float) $units;
            if ($units <= 0) {
                continue;
            }

            $onBill = isset($unitValues[$typeId]);
            $unitValue = $onBill ? (float) $unitValues[$typeId] : ($marketPrice ? $marketPrice($typeId) : null);
            $value = $unitValue !== null ? round($units * $unitValue, 2) : 0.0;

            $out['lines'][$typeId] = [
                'type_id' => $typeId,
                'units' => $units,
                'unit_value' => $unitValue,
                'value' => $value,
                'on_bill' => $onBill,
            ];
            $out['value'] += $value;
        }

        $out['value'] = round($out['value'], 2);

        return $out;
    }

    /**
     * The raw ore a compressed ore's name stands for: "Compressed Zeolites"
     * gives "Zeolites". Null when the name is not a compressed ore's.
     */
    public static function rawNameOf(string $name): ?string
    {
        if (preg_match('/^Compressed (.+)$/', trim($name), $m)) {
            return $m[1];
        }

        return null;
    }

    private static function withUnitValues(array $lines): array
    {
        foreach ($lines as &$line) {
            $line['unit_value'] = $line['units'] > 0 ? $line['isk'] / $line['units'] : 0.0;
        }
        unset($line);

        uasort($lines, fn ($a, $b) => $b['isk'] <=> $a['isk']);

        return $lines;
    }
}
