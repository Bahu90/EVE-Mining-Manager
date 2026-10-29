<?php

namespace MiningManager\Services\Character\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared "which characters belong to the same player" lookup.
 *
 * Anything that needs a player's alts pulls them from here.
 */
trait ResolvesCharacterOwnership
{
    /**
     * Every character belonging to the same player as the given one.
     *
     * The SeAT model: refresh_tokens.user_id is the cross-character link, so
     * every character a player has authenticated against the install shares a
     * user_id. Characters sharing a user_id are alts of the same player.
     *
     * Falls back to a single-element list (just the input character) when the
     * character isn't registered, or when the lookup fails for any reason. The
     * result always contains the input character, so it is safe to hand
     * straight to a whereIn().
     *
     * @param  int  $characterId
     * @return array<int>
     */
    protected function getCharacterIdsForUserOf(int $characterId): array
    {
        try {
            $userId = DB::table('refresh_tokens')
                ->where('character_id', $characterId)
                ->value('user_id');

            if ($userId === null) {
                // Unregistered character (guest miner, revoked token). Nothing
                // to widen to, so match strictly on the character itself.
                return [$characterId];
            }

            $charIds = DB::table('refresh_tokens')
                ->where('user_id', $userId)
                ->pluck('character_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!in_array($characterId, $charIds, true)) {
                $charIds[] = $characterId;
            }

            return $charIds;
        } catch (\Exception $e) {
            Log::warning('Mining Manager: character ownership lookup failed, falling back to strict character match', [
                'character_id' => $characterId,
                'error' => $e->getMessage(),
            ]);

            return [$characterId];
        }
    }
}
