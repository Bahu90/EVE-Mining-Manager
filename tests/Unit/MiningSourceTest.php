<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Support\MiningSource;
use PHPUnit\Framework\TestCase;

/**
 * Records the where-calls a query builder would receive, so the scope can be
 * checked without a database.
 */
class RecordingQuery
{
    public array $calls = [];

    public function __call($name, $arguments)
    {
        if ($name === 'where' && isset($arguments[0]) && $arguments[0] instanceof \Closure) {
            $inner = new self();
            $arguments[0]($inner);
            $this->calls[] = ['where(group)', $inner->calls];

            return $this;
        }

        $this->calls[] = array_merge([$name], $arguments);

        return $this;
    }
}

class MiningSourceTest extends TestCase
{
    protected function tearDown(): void
    {
        MiningSource::forget();
    }

    public function test_unknown_sources_fall_back_to_moon(): void
    {
        $this->assertSame('moon', MiningSource::normalise(null));
        $this->assertSame('moon', MiningSource::normalise('belts'));
        $this->assertSame('anomalies', MiningSource::normalise('anomalies'));
        $this->assertSame('all', MiningSource::normalise('all'));
    }

    public function test_moon_ore_is_moon_mining_wherever_it_was_logged(): void
    {
        $this->assertSame('moon', MiningSource::ofRow((object) ['observer_id' => 1046000000001, 'is_moon_ore' => true]));
        $this->assertSame('moon', MiningSource::ofRow((object) ['observer_id' => null, 'is_moon_ore' => true]));
        $this->assertSame('anomalies', MiningSource::ofRow((object) ['observer_id' => null, 'is_moon_ore' => false]));
        $this->assertSame('anomalies', MiningSource::ofRow(['observer_id' => null, 'is_moon_ore' => 0]));
    }

    public function test_moon_takes_refinery_rows_and_moon_ore_no_refinery_row_covers(): void
    {
        MiningSource::forget(98000001, true);
        $query = new RecordingQuery();

        MiningSource::scope($query, MiningSource::MOON);

        $this->assertSame(['whereNotNull', 'mining_ledger.processed_at'], $query->calls[0]);
        $this->assertSame('where(group)', $query->calls[1][0]);
        $this->assertSame('whereRaw', $query->calls[2][0]);
        $sql = $query->calls[2][1];
        $this->assertStringContainsString('mining_ledger.observer_id IS NOT NULL OR (mining_ledger.is_moon_ore = 1 AND NOT EXISTS', $sql);
        $this->assertStringContainsString('mm_obs.corporation_id = 98000001', $sql);
    }

    public function test_anomalies_never_hold_moon_ore(): void
    {
        MiningSource::forget(null, true);

        $this->assertSame(
            '(l.observer_id IS NULL AND l.is_moon_ore = 0)',
            MiningSource::condition(MiningSource::ANOMALIES, 'l')
        );
    }

    public function test_all_leaves_out_log_rows_a_refinery_row_covers(): void
    {
        MiningSource::forget(null, true);
        $sql = MiningSource::condition(MiningSource::ALL);

        $this->assertStringStartsWith('(mining_ledger.observer_id IS NOT NULL OR mining_ledger.is_moon_ore = 0 OR NOT EXISTS', $sql);
        $this->assertStringNotContainsString('mm_obs.corporation_id', $sql);
    }

    public function test_split_sums_use_the_same_conditions(): void
    {
        MiningSource::forget(null, true);

        $this->assertSame(
            'SUM(CASE WHEN (l.observer_id IS NULL AND l.is_moon_ore = 0) THEN l.total_value ELSE 0 END)',
            MiningSource::sumSql(MiningSource::ANOMALIES, 'l')
        );
    }
}
