{{--
    Moon / Anomalies / All switch. Shared by every page that shows mining.
    Optional: $source (defaults to the current pick), $showHint (default true).
    The pick is remembered for the session by MiningSource::current().
--}}
@php
    $mmSourceNow = $source ?? \MiningManager\Support\MiningSource::current();
    $mmSourceOptions = [
        \MiningManager\Support\MiningSource::MOON => ['icon' => 'fas fa-moon', 'label' => trans('mining-manager::ledger.source_moon')],
        \MiningManager\Support\MiningSource::ANOMALIES => ['icon' => 'fas fa-meteor', 'label' => trans('mining-manager::ledger.source_anomalies')],
        \MiningManager\Support\MiningSource::ALL => ['icon' => 'fas fa-layer-group', 'label' => trans('mining-manager::ledger.source_all')],
    ];
    $mmSourceShowHint = $showHint ?? true;
@endphp
<div class="mm-source-switch">
    <div class="btn-group btn-group-sm" role="group" aria-label="{{ trans('mining-manager::ledger.source_label') }}">
        @foreach($mmSourceOptions as $mmKey => $mmOption)
            <a href="{{ request()->fullUrlWithQuery([\MiningManager\Support\MiningSource::PARAM => $mmKey, 'page' => null]) }}"
               class="btn mm-source-btn {{ $mmSourceNow === $mmKey ? 'active' : '' }}"
               @if($mmSourceNow === $mmKey) aria-current="true" @endif>
                <i class="{{ $mmOption['icon'] }}"></i> {{ $mmOption['label'] }}
            </a>
        @endforeach
    </div>
    @if($mmSourceShowHint)
        <small class="mm-source-hint">{{ trans('mining-manager::ledger.source_hint_' . $mmSourceNow) }}</small>
    @endif
</div>
