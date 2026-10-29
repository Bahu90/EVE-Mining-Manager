<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Services\OreClassifier;
use MiningManager\Services\TypeIdRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The classifier decides which category every ledger row is filed under, so a
 * regression here silently moves mining between chart columns.
 */
class OreClassifierTest extends TestCase
{
    public function test_regular_ore_is_ore(): void
    {
        $this->assertSame('ore', OreClassifier::category(1230));      // Veldspar
    }

    public function test_moon_ore_carries_its_rarity(): void
    {
        $this->assertSame('moon_r4', OreClassifier::category(45490));   // Zeolites
        $this->assertSame('moon_r64', OreClassifier::category(45510));  // Xenotime
    }

    public function test_ice_gas_and_abyssal(): void
    {
        $this->assertSame('ice', OreClassifier::category(16262));       // Clear Icicle
        $this->assertSame('gas', OreClassifier::category(30370));       // Fullerite-C50
        $this->assertSame('abyssal', OreClassifier::category(52306));   // Talassonite
    }

    public function test_event_mutanite_and_zuthrine_are_ignored(): void
    {
        $this->assertTrue(OreClassifier::isIgnored(77118));   // Amperum Mutanite
        $this->assertTrue(OreClassifier::isIgnored(28626));   // Zuthrine
        $this->assertFalse(OreClassifier::isIgnored(1230));
    }

    public function test_unknown_type_falls_back_to_regular_ore(): void
    {
        $this->assertSame('ore', OreClassifier::category(999999999));
        $this->assertFalse(OreClassifier::isIgnored(999999999));
    }

    public function test_every_moon_ore_has_a_rarity(): void
    {
        foreach (TypeIdRegistry::MOON_ORES as $typeId) {
            $this->assertStringStartsWith(
                'moon_r',
                OreClassifier::category($typeId),
                "Moon ore {$typeId} has no rarity tier, so it would be filed as plain 'moon'."
            );
        }
    }

    public function test_no_type_is_both_ignored_and_moon_ore(): void
    {
        $overlap = array_intersect(OreClassifier::ignoredTypeIds(), TypeIdRegistry::MOON_ORES);
        $this->assertSame([], array_values($overlap));
    }
}
