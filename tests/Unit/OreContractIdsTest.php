<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Models\OreContract;
use PHPUnit\Framework\TestCase;

/**
 * Ore contract payments share the payment ledger with wallet transfers, so
 * their ids must never collide with a wallet journal id, and must be told
 * apart from them (the wallet month reset leaves contracts alone).
 */
class OreContractIdsTest extends TestCase
{
    public function test_contract_payment_ids_sit_above_journal_ids(): void
    {
        if (!class_exists(\Illuminate\Database\Eloquent\Model::class)) {
            $this->markTestSkipped('illuminate/database is not installed.');
        }

        $id = OreContract::paymentIdFor(201114455);

        $this->assertSame(OreContract::PAYMENT_ID_BASE + 201114455, $id);
        $this->assertTrue(OreContract::isContractPaymentId($id));
        $this->assertTrue($id < PHP_INT_MAX);
        // A wallet journal id today is around 2.3e10; far below the base.
        $this->assertFalse(OreContract::isContractPaymentId(23_456_789_012));
        $this->assertFalse(OreContract::isContractPaymentId(null));
    }
}
