<?php

namespace MiningManager\Services\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns an exception into a message that is safe to show in the browser.
 *
 * Messages the plugin throws on purpose ("This tax is already paid") are
 * meant for the user and pass through. Internal failures (database errors,
 * PHP errors, anything mentioning SQL) can reveal table names, column names,
 * queries and data, so they are logged in full under a short reference and
 * the user sees only that reference.
 */
final class PublicError
{
    public static function message(\Throwable $e): string
    {
        if (!self::isInternal($e)) {
            return $e->getMessage();
        }

        $ref = strtoupper(Str::random(8));

        Log::error("Mining Manager: internal error [{$ref}]: " . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);

        return "An internal error occurred (reference {$ref}). The details are in the SeAT log.";
    }

    public static function isInternal(\Throwable $e): bool
    {
        if ($e instanceof \Error || $e instanceof \ErrorException || $e instanceof \PDOException) {
            return true;
        }

        if (class_exists(\Illuminate\Database\QueryException::class)
            && $e instanceof \Illuminate\Database\QueryException) {
            return true;
        }

        $message = $e->getMessage();

        return stripos($message, 'SQLSTATE') !== false
            || stripos($message, 'select ') !== false && stripos($message, ' from ') !== false
            || str_contains($message, '/var/www/')
            || str_contains($message, 'Stack trace');
    }
}
