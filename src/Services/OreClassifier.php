<?php

namespace MiningManager\Services;

/**
 * What kind of ore a type id is, for the ledger and for reporting.
 *
 * TypeIdRegistry holds the ids. It says which ids are Bitumens and which are
 * Hezorime, and that is all it should ever say. Which category an ore falls
 * under is policy, not data, and it lives here.
 *
 * This used to be several copies (ProcessMiningLedgerCommand, ImportCharacter-
 * MiningCommand, BackfillOreTypeFlagsCommand, EventMiningAggregator,
 * LedgerSummaryService), written from each other and already drifting.
 */
final class OreClassifier
{
    /**
     * Whether an ore is left out of the plugin entirely.
     *
     * Event, quest and mission ore is not ongoing mining. Counting it puts
     * one-off spikes into the figures people use to judge normal activity.
     *
     * Mutanite is ongoing, but it cannot be reprocessed and a price source
     * often has no price for it. Counted, it went into the ledger as regular
     * ore through the fallback in category(), usually worth nothing, which
     * says nothing true about it.
     *
     * Both are skipped at import, so nothing further along ever sees them.
     * Only mining imported after this check exists is affected. Rows already in
     * a ledger stay as they were, like every other change to how ore is treated.
     */
    public static function isIgnored(int $typeId): bool
    {
        return in_array($typeId, self::ignoredTypeIds(), true);
    }

    /**
     * Every type id isIgnored() leaves out, for callers that filter a query.
     *
     * @return array<int>
     */
    public static function ignoredTypeIds(): array
    {
        return array_merge(
            TypeIdRegistry::EVENT_ORES,
            TypeIdRegistry::MUTANITE_ORES,
            TypeIdRegistry::OBJECTIVE_ORES
        );
    }

    /**
     * The ores that count as abyssal: the Bezdnacine, Rakovene and Talassonite
     * families.
     *
     * Easy to widen by mistake. The Deep Space Survey families (Mordunium,
     * Ytirium, Eifyrium, Ducinium) and the Ore Prospecting Array families
     * (Griemeer, Nocxite, Kylixium, Hezorime, Ueganite) are newer and sound as
     * though they belong here, but they are nullsec and wormhole asteroid ore
     * that refines into ordinary minerals. They are regular ore, and counting
     * them as abyssal would put them in the wrong column of every chart.
     *
     * @return array<int>
     */
    public static function abyssalTypeIds(): array
    {
        return TypeIdRegistry::ABYSSAL_ORES;
    }

    public static function isAbyssal(int $typeId): bool
    {
        return in_array($typeId, self::abyssalTypeIds(), true);
    }

    /**
     * The value written to mining_ledger.ore_category.
     *
     * Order matters and is the order the plugin has always used. Moon ore is
     * decided first because a moon rock is a moon rock whatever else it might
     * also be, and regular ore is the fallback rather than a category anything
     * is positively identified as.
     */
    public static function category(int $typeId): string
    {
        if (TypeIdRegistry::isMoonOre($typeId)) {
            $rarity = TypeIdRegistry::getMoonOreRarity($typeId);

            // No rarity means a moon ore the rarity map has not been told
            // about. Saying so is better than picking a tier: the only tier
            // worth guessing would be the cheapest, and quietly filing r64 as
            // r4 is the kind of wrong nobody notices.
            return $rarity ? 'moon_' . $rarity : 'moon';
        }

        if (TypeIdRegistry::isIce($typeId)) {
            return 'ice';
        }

        if (TypeIdRegistry::isGas($typeId)) {
            return 'gas';
        }

        if (self::isAbyssal($typeId)) {
            return 'abyssal';
        }

        if (TypeIdRegistry::isTriglavianOre($typeId)) {
            return 'triglavian';
        }

        return 'ore';
    }

}
