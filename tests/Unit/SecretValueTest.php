<?php

namespace MiningManager\Tests\Unit;

use Illuminate\Encryption\Encrypter;
use MiningManager\Services\Security\SecretValue;
use PHPUnit\Framework\TestCase;

class SecretValueTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Encrypter::class)) {
            $this->markTestSkipped('illuminate/encryption is not installed.');
        }

        SecretValue::useEncrypter(new Encrypter(random_bytes(32), 'aes-256-cbc'));
    }

    protected function tearDown(): void
    {
        SecretValue::useEncrypter(null);
    }

    public function test_seal_and_open_round_trip(): void
    {
        $url = 'https://discord.com/api/webhooks/123/secret-token';
        $sealed = SecretValue::seal($url);

        $this->assertTrue(SecretValue::isSealed($sealed));
        $this->assertStringNotContainsString('secret-token', $sealed);
        $this->assertSame($url, SecretValue::open($sealed));
    }

    public function test_sealing_twice_does_not_double_encrypt(): void
    {
        $sealed = SecretValue::seal('https://example.com/a');
        $this->assertSame($sealed, SecretValue::seal($sealed));
    }

    public function test_legacy_plain_values_pass_through(): void
    {
        $this->assertSame('https://discord.com/api/webhooks/1/a', SecretValue::open('https://discord.com/api/webhooks/1/a'));
        $this->assertNull(SecretValue::open(null));
        $this->assertSame('', SecretValue::open(''));
        $this->assertSame('', SecretValue::seal(''));
    }

    public function test_hint_shows_the_host_only(): void
    {
        $this->assertSame('discord.com/…', SecretValue::hint('https://discord.com/api/webhooks/1/secret'));
        $this->assertNull(SecretValue::hint(''));
    }
}
