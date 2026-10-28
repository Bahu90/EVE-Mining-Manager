<?php

namespace MiningManager\Services\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

/**
 * Encrypts credentials at rest: webhook URLs and the Slack webhook setting.
 *
 * Anyone who can post to a Discord or Slack webhook URL can post as that
 * channel, so a database dump or backup should not hand them out. Values are
 * stored as "enc:" followed by Laravel's encrypted string, using SeAT's
 * APP_KEY.
 *
 * Values written before encryption existed have no prefix and are returned as
 * they are, so an install keeps working between upgrading and the migration
 * that seals the old rows.
 */
final class SecretValue
{
    public const PREFIX = 'enc:';

    /** Swapped in by tests; the application's encrypter otherwise. */
    private static ?Encrypter $encrypter = null;

    public static function useEncrypter(?Encrypter $encrypter): void
    {
        self::$encrypter = $encrypter;
    }

    public static function isSealed(?string $value): bool
    {
        return $value !== null && str_starts_with($value, self::PREFIX);
    }

    /**
     * Encrypt a plain value. Empty values stay empty and an already sealed
     * value is not sealed twice.
     */
    public static function seal(?string $plain): ?string
    {
        if ($plain === null || $plain === '' || self::isSealed($plain)) {
            return $plain;
        }

        return self::PREFIX . self::encrypter()->encryptString($plain);
    }

    /**
     * Decrypt a stored value. Legacy plain values pass through. A sealed
     * value that no longer decrypts (APP_KEY changed) comes back as null, so
     * the caller treats it as not configured instead of posting garbage.
     */
    public static function open(?string $stored): ?string
    {
        if ($stored === null || $stored === '' || !self::isSealed($stored)) {
            return $stored;
        }

        try {
            return self::encrypter()->decryptString(substr($stored, strlen(self::PREFIX)));
        } catch (DecryptException $e) {
            Log::warning('Mining Manager: a stored webhook URL could not be decrypted. '
                . 'Was APP_KEY changed? Enter the URL again under Settings.');

            return null;
        }
    }

    /**
     * A short, safe description of a URL for showing in the interface:
     * the host only, never the token in the path.
     */
    public static function hint(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host ? $host . '/…' : 'saved';
    }

    private static function encrypter(): Encrypter
    {
        return self::$encrypter ?? app('encrypter');
    }
}
