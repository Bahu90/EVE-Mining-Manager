<?php

namespace MiningManager\Support;

/**
 * URLs for the plugin's published scripts and stylesheets, stamped with the
 * time the file last changed. A browser keeps a file it has seen, so without
 * the stamp an update can leave a page drawn with the old stylesheet; with a
 * fixed "?v=8" it never changes at all.
 */
final class Asset
{
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        $source = __DIR__ . '/../Resources/assets/' . $path;
        $stamp = is_file($source) ? filemtime($source) : false;

        return asset('vendor/mining-manager/' . $path) . ($stamp ? '?v=' . $stamp : '');
    }
}
