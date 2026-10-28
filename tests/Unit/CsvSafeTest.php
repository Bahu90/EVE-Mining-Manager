<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Services\Security\CsvSafe;
use PHPUnit\Framework\TestCase;

class CsvSafeTest extends TestCase
{
    public function test_formula_starts_are_neutralised(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", CsvSafe::cell('=HYPERLINK("http://x")'));
        $this->assertSame("'+cmd", CsvSafe::cell('+cmd'));
        $this->assertSame("'-1+2", CsvSafe::cell('-1+2'));
        $this->assertSame("'@SUM(A1)", CsvSafe::cell('@SUM(A1)'));
        $this->assertSame("'\tx", CsvSafe::cell("\tx"));
    }

    public function test_numbers_and_plain_text_are_unchanged(): void
    {
        $this->assertSame(-1500, CsvSafe::cell(-1500));
        $this->assertSame('-1500.25', CsvSafe::cell('-1500.25'));
        $this->assertSame(12.5, CsvSafe::cell(12.5));
        $this->assertSame('Veldspar', CsvSafe::cell('Veldspar'));
        $this->assertSame('', CsvSafe::cell(''));
        $this->assertNull(CsvSafe::cell(null));
    }

    public function test_row_applies_to_every_cell(): void
    {
        $this->assertSame(['Bob', "'=1+1", 5], CsvSafe::row(['Bob', '=1+1', 5]));
    }
}
