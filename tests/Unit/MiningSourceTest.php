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

    public function test_a_row_with_an_observer_is_moon_mining(): void
    {
        $this->assertSame('moon', MiningSource::ofRow((object) ['observer_id' => 1046000000001]));
        $this->assertSame('anomalies', MiningSource::ofRow((object) ['observer_id' => null]));
        $this->assertSame('anomalies', MiningSource::ofRow(['observer_id' => null]));
    }

    public function test_moon_keeps_observer_rows_of_the_moon_owner(): void
    {
        MiningSource::forget(98000001, true);
        $query = new RecordingQuery();

        MiningSource::scope($query, MiningSource::MOON);

        $this->assertSame([
            ['whereNotNull', 'mining_ledger.processed_at'],
            ['where(group)', [
                ['whereNull', 'mining_ledger.corporation_id'],
                ['orWhere', 'mining_ledger.corporation_id', 98000001],
            ]],
            ['whereNotNull', 'mining_ledger.observer_id'],
        ], $query->calls);
    }

    public function test_anomalies_keep_rows_without_an_observer(): void
    {
        MiningSource::forget(null, true);
        $query = new RecordingQuery();

        MiningSource::scope($query, MiningSource::ANOMALIES, 'l');

        $this->assertSame([
            ['whereNotNull', 'l.processed_at'],
            ['whereNull', 'l.observer_id'],
        ], $query->calls);
    }

    public function test_all_adds_no_source_condition(): void
    {
        MiningSource::forget(null, true);
        $query = new RecordingQuery();

        MiningSource::scope($query, MiningSource::ALL);

        $this->assertSame([['whereNotNull', 'mining_ledger.processed_at']], $query->calls);
    }

    public function test_split_sums_are_complementary(): void
    {
        $this->assertSame(
            'SUM(CASE WHEN l.observer_id IS NOT NULL THEN l.total_value ELSE 0 END)',
            MiningSource::sumSql(MiningSource::MOON, 'l.total_value', 'l.observer_id')
        );
        $this->assertSame(
            'SUM(CASE WHEN observer_id IS NULL THEN total_value ELSE 0 END)',
            MiningSource::sumSql(MiningSource::ANOMALIES)
        );
    }
}
