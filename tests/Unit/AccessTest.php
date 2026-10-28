<?php

namespace MiningManager\Tests\Unit;

use MiningManager\Support\Access;
use PHPUnit\Framework\TestCase;

class AccessTest extends TestCase
{
    private function userWith(array $abilities): object
    {
        return new class($abilities) {
            public function __construct(private array $abilities) {}
            public function canAny($abilities): bool { return (bool) array_intersect((array) $abilities, $this->abilities); }
        };
    }

    public function test_view_all_reads_the_corporation(): void
    {
        $this->assertTrue(Access::readsCorporation($this->userWith(['mining-manager.member', 'mining-manager.view_all'])));
    }

    public function test_directors_and_admins_read_it_too(): void
    {
        $this->assertTrue(Access::readsCorporation($this->userWith(['mining-manager.director'])));
        $this->assertTrue(Access::readsCorporation($this->userWith(['mining-manager.admin'])));
    }

    public function test_a_plain_member_or_moon_manager_does_not(): void
    {
        $this->assertFalse(Access::readsCorporation($this->userWith(['mining-manager.member'])));
        $this->assertFalse(Access::readsCorporation($this->userWith(['mining-manager.moon_manager'])));
    }
}
