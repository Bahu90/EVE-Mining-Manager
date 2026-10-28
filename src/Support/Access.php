<?php

namespace MiningManager\Support;

/**
 * Who may read the corporation's figures, not only their own.
 *
 * Directors and admins always can. So can anyone given "See all data
 * (read-only)" (mining-manager.view_all) on SeAT's role page: they read the
 * corporation's mining, guests, analytics, the Moon Planner and Metenox
 * Cargo, and change nothing.
 *
 * The same rule is the gate READS, for routes (can:mining-manager.reads_corporation)
 * and views (@can('mining-manager.reads_corporation')).
 */
final class Access
{
    public const READS = 'mining-manager.reads_corporation';

    public const VIEW_ALL = 'mining-manager.view_all';

    public const ABILITIES = [
        self::VIEW_ALL,
        'mining-manager.director',
        'mining-manager.admin',
    ];

    /**
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user the person looking; the logged-in user when null
     */
    public static function readsCorporation($user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user !== null && method_exists($user, 'canAny') && $user->canAny(self::ABILITIES);
    }
}
