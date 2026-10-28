<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Tools\ThemePagesBuilder;
use PHPUnit\Framework\TestCase;

/**
 * mm-theme-pages.css is generated from the pages' own styles. When a page's
 * styles change and the file is not rebuilt, the EVE-RO look quietly misses
 * the new colours; this fails instead. Fix: php tools/build-theme-pages.php
 */
class ThemePagesCssTest extends TestCase
{
    public function test_generated_page_colours_are_up_to_date(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/tools/build-theme-pages.php';

        $expected = (new ThemePagesBuilder($root))->build();
        $actual = file_get_contents($root . '/src/Resources/assets/css/mm-theme-pages.css');

        $this->assertTrue(
            $expected === $actual,
            'mm-theme-pages.css is out of date. Run: php tools/build-theme-pages.php'
        );
    }

    public function test_purple_accents_are_mapped(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/assets/css/mm-theme-pages.css');

        $this->assertStringNotContainsString('#667eea', strtolower($css));
        $this->assertStringNotContainsString('102, 126, 234', $css);
        $this->assertStringContainsString('var(--mmx-blue)', $css);
    }
}
