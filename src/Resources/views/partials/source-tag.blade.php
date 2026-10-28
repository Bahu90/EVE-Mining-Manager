{{-- Where one ledger row was mined. Expects $row. --}}
@if(\MiningManager\Support\MiningSource::ofRow($row) === \MiningManager\Support\MiningSource::MOON)
    <span class="mm-source-tag moon" title="{{ trans('mining-manager::ledger.source_hint_moon') }}"><i class="fas fa-moon"></i> {{ trans('mining-manager::ledger.source_tag_moon') }}</span>
@else
    <span class="mm-source-tag anomalies" title="{{ trans('mining-manager::ledger.source_hint_anomalies') }}"><i class="fas fa-meteor"></i> {{ trans('mining-manager::ledger.source_tag_anomalies') }}</span>
@endif
