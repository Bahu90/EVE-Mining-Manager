<?php

namespace MiningManager\Services\Security;

/**
 * Decides whether the plugin may post to a webhook URL.
 *
 * The server sends these requests from inside the SeAT network, so an
 * unchecked URL lets anyone who can save one reach services that are not
 * meant to be reachable: the database, Redis, the Docker host, or cloud
 * metadata endpoints. A webhook must be HTTPS, must not carry credentials,
 * and its host must be a public name or address.
 */
final class WebhookUrlPolicy
{
    /** Hostnames and suffixes that only ever mean "inside this network". */
    private const INTERNAL_HOSTS = ['localhost'];
    private const INTERNAL_SUFFIXES = ['.localhost', '.local', '.internal', '.lan', '.home.arpa'];

    /**
     * Null when the URL is acceptable, otherwise a reason to show the admin.
     *
     * @param callable|null $resolve fn(string $host): string[] of IP addresses.
     *                               Defaults to DNS; tests pass their own.
     */
    public static function problem(?string $url, ?callable $resolve = null): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return 'The webhook URL is empty.';
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'The webhook URL is not a valid address.';
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return 'Webhook URLs must start with https://.';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'Webhook URLs must not contain a user name or password.';
        }

        $host = strtolower(rtrim(trim($parts['host'], '[]'), '.'));

        if (in_array($host, self::INTERNAL_HOSTS, true)) {
            return 'Webhooks cannot point at this server.';
        }

        foreach (self::INTERNAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return 'Webhooks cannot point at an internal host name.';
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host)
                ? null
                : 'Webhooks cannot point at a private or reserved IP address.';
        }

        // A bare name like "mariadb" or "redis" is a Docker service, not a
        // website.
        if (!str_contains($host, '.')) {
            return 'Webhooks cannot point at an internal host name.';
        }

        $addresses = ($resolve ?? [self::class, 'resolve'])($host);

        if (empty($addresses)) {
            return "The host {$host} could not be found.";
        }

        foreach ($addresses as $ip) {
            if (!self::isPublicIp($ip)) {
                return "The host {$host} points at a private address.";
            }
        }

        return null;
    }

    public static function isAllowed(?string $url, ?callable $resolve = null): bool
    {
        return self::problem($url, $resolve) === null;
    }

    /**
     * A Laravel validation rule: ['required', WebhookUrlPolicy::rule()].
     */
    public static function rule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $problem = self::problem((string) $value);

            if ($problem !== null) {
                $fail($problem);
            }
        };
    }

    public static function isPublicIp(string $ip): bool
    {
        // An IPv4 address written as IPv6 (::ffff:127.0.0.1) is judged as the
        // IPv4 address it is.
        if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) {
            $ip = $m[1];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Ranges PHP's flags do not cover on every version: carrier-grade NAT
        // (100.64.0.0/10), loopback and link-local, and IPv6 unique-local.
        $blocked = ['100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '0.0.0.0/8', 'fc00::/7', 'fe80::/10', '::1/128', '::/128'];
        foreach ($blocked as $cidr) {
            if (self::inCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    private static function inCidr(string $ip, string $cidr): bool
    {
        [$net, $bits] = explode('/', $cidr);
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton($net);

        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $rest = $bits % 8;

        if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }

        if ($rest === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
    }

    /** @return string[] */
    private static function resolve(string $host): array
    {
        $v4 = @gethostbynamel($host) ?: [];
        $v6 = [];

        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (!empty($record['ipv6'])) {
                $v6[] = $record['ipv6'];
            }
        }

        return array_merge($v4, $v6);
    }
}
