<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Services\Security\WebhookUrlPolicy;
use PHPUnit\Framework\TestCase;

class WebhookUrlPolicyTest extends TestCase
{
    /** DNS stand-in: every name resolves to a public Discord address. */
    private function publicDns(): callable
    {
        return fn (string $host) => ['162.159.135.232'];
    }

    public function test_discord_and_slack_webhooks_are_allowed(): void
    {
        $this->assertNull(WebhookUrlPolicy::problem('https://discord.com/api/webhooks/123/abc', $this->publicDns()));
        $this->assertNull(WebhookUrlPolicy::problem('https://hooks.slack.com/services/T/B/x', $this->publicDns()));
    }

    public function test_http_and_other_schemes_are_refused(): void
    {
        $this->assertNotNull(WebhookUrlPolicy::problem('http://discord.com/api/webhooks/1/a', $this->publicDns()));
        $this->assertNotNull(WebhookUrlPolicy::problem('file:///etc/passwd', $this->publicDns()));
        $this->assertNotNull(WebhookUrlPolicy::problem('gopher://example.com/', $this->publicDns()));
        $this->assertNotNull(WebhookUrlPolicy::problem('', $this->publicDns()));
        $this->assertNotNull(WebhookUrlPolicy::problem('not a url', $this->publicDns()));
    }

    public function test_internal_hosts_are_refused(): void
    {
        foreach ([
            'https://localhost/hook',
            'https://mariadb:3306/',
            'https://redis/',
            'https://seat.local/hook',
            'https://metadata.google.internal/',
            'https://127.0.0.1/hook',
            'https://10.0.0.5/hook',
            'https://172.18.0.3/hook',
            'https://192.168.1.10/hook',
            'https://169.254.169.254/latest/meta-data',
            'https://100.64.1.1/hook',
            'https://[::1]/hook',
            'https://[::ffff:127.0.0.1]/hook',
            'https://[fd00::1]/hook',
        ] as $url) {
            $this->assertNotNull(WebhookUrlPolicy::problem($url, $this->publicDns()), "{$url} should be refused");
        }
    }

    public function test_a_public_name_that_resolves_privately_is_refused(): void
    {
        $privateDns = fn (string $host) => ['10.0.0.7'];
        $this->assertNotNull(WebhookUrlPolicy::problem('https://evil.example.com/hook', $privateDns));

        $mixedDns = fn (string $host) => ['162.159.135.232', '127.0.0.1'];
        $this->assertNotNull(WebhookUrlPolicy::problem('https://evil.example.com/hook', $mixedDns));
    }

    public function test_a_name_that_does_not_resolve_is_refused(): void
    {
        $noDns = fn (string $host) => [];
        $this->assertNotNull(WebhookUrlPolicy::problem('https://nowhere.example.com/hook', $noDns));
    }

    public function test_credentials_in_the_url_are_refused(): void
    {
        $this->assertNotNull(WebhookUrlPolicy::problem('https://user:pass@discord.com/api/webhooks/1/a', $this->publicDns()));
    }

    public function test_public_ip_literal_is_allowed(): void
    {
        $this->assertNull(WebhookUrlPolicy::problem('https://162.159.135.232/hook', $this->publicDns()));
        $this->assertTrue(WebhookUrlPolicy::isPublicIp('8.8.8.8'));
        $this->assertFalse(WebhookUrlPolicy::isPublicIp('192.168.0.1'));
    }
}
