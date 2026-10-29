<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Services\Security\PublicError;
use PHPUnit\Framework\TestCase;

class PublicErrorTest extends TestCase
{
    public function test_the_plugins_own_messages_are_not_internal(): void
    {
        $this->assertFalse(PublicError::isInternal(new \Exception('This event has already ended.')));
        $this->assertFalse(PublicError::isInternal(new \RuntimeException('Webhook URL refused: Webhook URLs must start with https://.')));
    }

    public function test_database_and_php_errors_are_internal(): void
    {
        $this->assertTrue(PublicError::isInternal(new \Exception("SQLSTATE[42S22]: Column not found: 1054 Unknown column 'x'")));
        $this->assertTrue(PublicError::isInternal(new \PDOException('connection refused')));
        $this->assertTrue(PublicError::isInternal(new \TypeError('Argument #1 must be of type int')));
        $this->assertTrue(PublicError::isInternal(new \ErrorException('Undefined array key "x"')));
        $this->assertTrue(PublicError::isInternal(new \Exception('Error in select id from mining_ledger where x')));
        $this->assertTrue(PublicError::isInternal(new \Exception('failed in /var/www/seat/vendor/x.php')));
    }

    public function test_plugin_messages_pass_through_unchanged(): void
    {
        $this->assertSame('Nothing to export.', PublicError::message(new \Exception('Nothing to export.')));
    }
}
