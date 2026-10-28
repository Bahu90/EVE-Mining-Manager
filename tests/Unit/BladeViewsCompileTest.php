<?php

namespace MiningManager\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;

/**
 * Every view compiles to valid PHP.
 *
 * Blade has traps that only show when a page is opened: a one-line @php(...)
 * before a @php ... @endphp block swallows everything in between, and @json()
 * splits its argument on commas. Both produce a compiled view PHP cannot
 * parse. This compiles each view and lints the result, so they fail here.
 */
class BladeViewsCompileTest extends TestCase
{
    public function test_every_view_compiles_to_valid_php(): void
    {
        if (!class_exists(BladeCompiler::class)) {
            $this->markTestSkipped('illuminate/view is not installed.');
        }

        $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
        $root = dirname(__DIR__, 2) . '/src/Resources/views';
        $failures = [];
        $count = 0;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            if (!str_ends_with((string) $file, '.blade.php')) {
                continue;
            }
            $count++;

            $tmp = tempnam(sys_get_temp_dir(), 'mmview') . '.php';
            file_put_contents($tmp, $compiler->compileString(file_get_contents((string) $file)));
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $code);
            unlink($tmp);

            if ($code !== 0) {
                $failures[] = substr((string) $file, strlen($root) + 1) . ': ' . implode(' ', $output);
            }
            $output = [];
        }

        $this->assertGreaterThan(0, $count);
        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
