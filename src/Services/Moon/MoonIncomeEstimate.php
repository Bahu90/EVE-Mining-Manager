<?php

namespace MiningManager\Services\Moon;

use Illuminate\Support\Facades\DB;

/**
 * What a Metenox Moon Drill on a moon would collect over one extraction
 * period: it keeps everything it drills. Before fuel: fuel rates and fuel
 * prices differ too much between corporations to guess.
 */
class MoonIncomeEstimate
{
    /**
     * @var MoonValuation
     */
    protected $valuation;

    public function __construct(MoonValuation $valuation)
    {
        $this->valuation = $valuation;
    }

    /**
     * The drill's haul for a simulation, over the simulation's own days.
     *
     * @param array $simulation what MoonExtractionService::simulateExtraction() returned
     */
    public function forSimulation(array $simulation): array
    {
        $moonId = (int) ($simulation['moon_id'] ?? 0);
        $days = (int) ($simulation['extraction_days'] ?? 14);

        $metenox = $this->valuation->metenox($this->moonOres($moonId), $days);
        $bayM3 = 0.0;
        foreach ($metenox['materials'] as $material) {
            $bayM3 += $material['quantity'] * MetenoxCargoService::MOON_MATERIAL_VOLUME_M3;
        }

        return [
            'days' => $days,
            'metenox' => [
                'value' => $metenox['value'],
                'materials' => $metenox['materials'],
                'unpriced' => $metenox['unpriced'],
                'bay_m3' => $bayM3,
                'bay_capacity_m3' => MetenoxCargoService::MOON_MATERIAL_BAY_CAPACITY_M3,
                'bay_full_days' => $bayM3 > 0
                    ? MetenoxCargoService::MOON_MATERIAL_BAY_CAPACITY_M3 / ($bayM3 / $days)
                    : null,
            ],
        ];
    }

    /**
     * @return array<int, float> ore type => share of the moon
     */
    protected function moonOres(int $moonId): array
    {
        return DB::table('universe_moon_contents')
            ->where('moon_id', $moonId)
            ->pluck('rate', 'type_id')
            ->map(fn ($rate) => (float) $rate)
            ->all();
    }
}
