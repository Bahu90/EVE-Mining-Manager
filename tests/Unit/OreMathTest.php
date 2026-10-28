<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Services\Tax\Ore\OreMath;
use PHPUnit\Framework\TestCase;

class OreMathTest extends TestCase
{
    private function ores(): array
    {
        return [
            // 1000 Zeolites (R4) worth 100,000, taxed at 10%: 100 units owed
            ['type_id' => 45490, 'ore_name' => 'Zeolites', 'quantity' => 1000, 'total_value' => 100000, 'estimated_tax' => 10000],
            // the same ore on another day, half taxed (event): 25 units
            ['type_id' => 45490, 'ore_name' => 'Zeolites', 'quantity' => 500, 'total_value' => 50000, 'estimated_tax' => 2500],
            // 200 Xenotime (R64) worth 400,000, taxed at 25%: 50 units
            ['type_id' => 45510, 'ore_name' => 'Xenotime', 'quantity' => 200, 'total_value' => 400000, 'estimated_tax' => 100000],
            // untaxed ore is not owed
            ['type_id' => 1230, 'ore_name' => 'Veldspar', 'quantity' => 9999, 'total_value' => 1000, 'estimated_tax' => 0],
        ];
    }

    public function test_each_ore_is_owed_at_its_own_rate(): void
    {
        $lines = OreMath::linesFromOres($this->ores());

        $this->assertCount(2, $lines);
        $this->assertEqualsWithDelta(125.0, $lines[45490]['units'], 0.0001);
        $this->assertEqualsWithDelta(12500.0, $lines[45490]['isk'], 0.0001);
        $this->assertEqualsWithDelta(50.0, $lines[45510]['units'], 0.0001);
        $this->assertEqualsWithDelta(2000.0, $lines[45510]['unit_value'], 0.0001);
        // Largest value first.
        $this->assertSame(45510, array_key_first($lines));
    }

    public function test_scaling_to_the_bill_keeps_unit_values(): void
    {
        $lines = OreMath::linesFromOres($this->ores());
        // The lines add up to 112,500 ISK. Say a minimum charge raised the bill
        // to 225,000: every ore's units double, and its unit value stays put.
        $scaled = OreMath::scaleTo($lines, 225000);

        $this->assertEqualsWithDelta(250.0, $scaled[45490]['units'], 0.0001);
        $this->assertEqualsWithDelta(100.0, $scaled[45510]['units'], 0.0001);
        $this->assertEqualsWithDelta(225000.0, array_sum(array_column($scaled, 'isk')), 0.01);
        $this->assertEqualsWithDelta($lines[45510]['unit_value'], $scaled[45510]['unit_value'], 0.0001);
        $this->assertSame([], OreMath::scaleTo($lines, 0));
    }

    public function test_handing_in_exactly_the_list_pays_exactly_the_bill(): void
    {
        $lines = OreMath::scaleTo(OreMath::linesFromOres($this->ores()), 112500);
        $unitValues = array_map(fn ($l) => $l['unit_value'], $lines);
        $items = array_map(fn ($l) => $l['units'], $lines);

        $paid = OreMath::valueHandedIn($items, $unitValues);
        $this->assertEqualsWithDelta(112500.0, $paid['value'], 0.05);
    }

    public function test_ore_not_on_the_bill_uses_the_market_price_or_nothing(): void
    {
        $paid = OreMath::valueHandedIn([45490 => 10, 1230 => 1000], [45490 => 100.0], fn ($t) => $t === 1230 ? 2.5 : null);
        $this->assertEqualsWithDelta(3500.0, $paid['value'], 0.001);
        $this->assertTrue($paid['lines'][45490]['on_bill']);
        $this->assertFalse($paid['lines'][1230]['on_bill']);

        $unpriced = OreMath::valueHandedIn([1230 => 1000], [45490 => 100.0]);
        $this->assertEqualsWithDelta(0.0, $unpriced['value'], 0.001);
        $this->assertNull($unpriced['lines'][1230]['unit_value']);
    }

    public function test_units_are_rounded_up(): void
    {
        $this->assertSame(101, OreMath::unitsToHandIn(100.2));
        $this->assertSame(100, OreMath::unitsToHandIn(100.0000001));
        $this->assertSame(0, OreMath::unitsToHandIn(0));
    }

    public function test_compressed_names(): void
    {
        $this->assertSame('Zeolites', OreMath::rawNameOf('Compressed Zeolites'));
        $this->assertSame('Glistening Bitumens', OreMath::rawNameOf('Compressed Glistening Bitumens'));
        $this->assertNull(OreMath::rawNameOf('Zeolites'));
    }
}
