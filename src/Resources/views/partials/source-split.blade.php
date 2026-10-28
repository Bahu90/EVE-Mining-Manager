{{--
    Moon against anomalies, as one bar with both figures.
    Expects $split = ['moon' => ISK, 'anomalies' => ISK].
--}}
@php
    $mmSplitMoon = (float) ($split['moon'] ?? 0);
    $mmSplitAnomalies = (float) ($split['anomalies'] ?? 0);
    $mmSplitTotal = $mmSplitMoon + $mmSplitAnomalies;
    $mmSplitMoonPct = $mmSplitTotal > 0 ? round($mmSplitMoon / $mmSplitTotal * 100, 1) : 0;
@endphp
@if($mmSplitTotal > 0)
    <div class="mm-source-split" title="{{ trans('mining-manager::ledger.source_hint_all') }}">
        <div class="mm-source-split-bar">
            <span class="moon" style="width: {{ $mmSplitMoonPct }}%"></span>
            <span class="anomalies" style="width: {{ 100 - $mmSplitMoonPct }}%"></span>
        </div>
        <div class="mm-source-split-legend">
            <span><span class="dot moon"></span>{{ trans('mining-manager::ledger.source_moon') }}
                <strong>{{ \MiningManager\Support\IskFormat::short($mmSplitMoon) }}</strong> ({{ $mmSplitMoonPct }}%)</span>
            <span><span class="dot anomalies"></span>{{ trans('mining-manager::ledger.source_anomalies') }}
                <strong>{{ \MiningManager\Support\IskFormat::short($mmSplitAnomalies) }}</strong> ({{ round(100 - $mmSplitMoonPct, 1) }}%)</span>
        </div>
    </div>
@endif
