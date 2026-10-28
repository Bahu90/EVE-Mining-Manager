<?php

namespace MiningManager\Tests\Unit;

use Carbon\Carbon;
use MiningManager\Services\Metrics\MetricsMath;
use MiningManager\Support\IskFormat;
use PHPUnit\Framework\TestCase;

class MetricsMathTest extends TestCase
{
    public function test_isk_is_written_like_the_corporation_page(): void
    {
        $this->assertSame('76.98 B', IskFormat::short(76_980_000_000));
        $this->assertSame('325.55 M', IskFormat::short(325_550_000));
        $this->assertSame('749 K', IskFormat::short(749_400));
        $this->assertSame('0', IskFormat::short(0));
        $this->assertSame('512', IskFormat::short(512));
        $this->assertSame('1.00 B', IskFormat::short(999_999_999));
        $this->assertSame('-2.50 M', IskFormat::short(-2_500_000));
        $this->assertSame('129 M', IskFormat::short(129_400_000, 0));
    }

    public function test_months_ending_with(): void
    {
        $months = MetricsMath::monthsEndingWith(Carbon::create(2026, 9, 15), 12);
        $this->assertCount(12, $months);
        $this->assertSame('2025-10', $months[0]);
        $this->assertSame('2026-09', $months[11]);
    }

    public function test_days_stop_at_today_in_the_current_month(): void
    {
        $days = MetricsMath::daysOf(Carbon::create(2026, 10, 1), Carbon::create(2026, 10, 28, 11));
        $this->assertCount(28, $days);
        $this->assertSame('2026-10-28', end($days));

        $september = MetricsMath::daysOf(Carbon::create(2026, 9, 1), Carbon::create(2026, 10, 28));
        $this->assertCount(30, $september);
    }

    public function test_series_fills_gaps_with_zero(): void
    {
        $series = MetricsMath::series(['a', 'b', 'c'], ['b' => ['isk' => 5, 'tax' => 1, 'volume' => 2]]);
        $this->assertSame([0.0, 5.0, 0.0], $series['isk']);
        $this->assertSame([0.0, 1.0, 0.0], $series['tax']);
        $this->assertSame(['a', 'b', 'c'], $series['labels']);
    }

    public function test_alts_fold_into_their_main_and_guests_stand_alone(): void
    {
        $players = MetricsMath::players(
            [
                1 => ['isk' => 100, 'tax' => 10, 'volume' => 50, 'quantity' => 5],   // main
                2 => ['isk' => 200, 'tax' => 20, 'volume' => 70, 'quantity' => 7],   // alt of 1
                9 => ['isk' => 300, 'tax' => 0, 'volume' => 90, 'quantity' => 9],    // guest
            ],
            [1 => 1, 2 => 1],
            [1 => 'Main Pilot', 2 => 'Alt Pilot', 9 => 'Visitor']
        );

        $this->assertCount(2, $players);
        $this->assertSame('Main Pilot', $players[0]['name']);
        $this->assertEqualsWithDelta(120.0, $players[0]['volume'], 0.001);
        $this->assertSame(2, $players[0]['characters']);
        $this->assertFalse($players[0]['guest']);
        $this->assertSame('Visitor', $players[1]['name']);
        $this->assertTrue($players[1]['guest']);
    }

    public function test_refinery_counts(): void
    {
        $counts = MetricsMath::refineryCounts(['ready', 'unstable', 'extracting', 'expired', 'extracting', 'none']);
        $this->assertSame(['online' => 6, 'ready' => 2, 'extracting' => 2, 'idle' => 2], $counts);
    }
}
