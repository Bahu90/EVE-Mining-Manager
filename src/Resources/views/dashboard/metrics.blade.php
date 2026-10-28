{{--
    Mining Metrics: the corporation's moon mining for one month, laid out like
    eve-ro.com/tools/mining. Data from MiningMetricsService, through
    DashboardController@metrics. Works in both looks; the EVE-RO look comes
    from partials.theme.
--}}
@extends('web::layouts.grids.12')

@section('title', trans('mining-manager::metrics.page_title'))
@section('page_header', trans('mining-manager::metrics.page_title'))

@push('head')
@include('mining-manager::partials.head')
@include('mining-manager::partials.theme')
@endpush

@php
    $fmt = fn ($v, $d = 2) => \MiningManager\Support\IskFormat::short($v, $d);
    $tierClass = fn (?string $tier) => $tier ? 'mmx-' . strtolower($tier) : 'mmx-rnone';
    $corpLabel = ($data['configured'] ?? false)
        ? ($data['corporation']['ticker'] ?? $data['corporation']['name'])
        : '';
    $monthStart = $month->copy()->startOfMonth();
    $monthEnd = $month->isSameMonth(now()) ? now() : $month->copy()->endOfMonth();
    $baseUrl = route('mining-manager.dashboard');
    $monthQuery = function (string $ym) use ($data) {
        $q = ['month' => $ym];
        if (($data['trendMonths'] ?? 12) !== 12) {
            $q['months'] = $data['trendMonths'];
        }
        return $q;
    };
@endphp

@section('full')
<div class="mining-manager-wrapper mm-metrics">

    @include('mining-manager::dashboard.partials._tax_banner')

    {{-- Head: the month as the title --}}
    <div class="mmx-head">
        <div>
            <div class="mmx-eyebrow">
                {{ trans('mining-manager::metrics.eyebrow') }}@if($corpLabel) &middot; {{ $corpLabel }}@endif
            </div>
            <h1 class="mmx-title">{{ strtoupper($month->format('M Y')) }}</h1>
            @if($data['configured'] ?? false)
                <div class="mmx-subtitle">{{ trans('mining-manager::metrics.subtitle', ['corp' => $data['corporation']['name']]) }}</div>
            @endif
        </div>
        <div class="mmx-actions">
            <a class="mmx-btn mmx-btn-ghost" href="{{ route('mining-manager.ledger.my-mining') }}">
                <i class="fas fa-user-astronaut"></i> {{ trans('mining-manager::metrics.my_mining') }}
            </a>
            <a class="mmx-btn mmx-btn-ghost" href="{{ route('mining-manager.taxes.my-taxes') }}">
                <i class="fas fa-coins"></i> {{ trans('mining-manager::metrics.my_taxes') }}
            </a>
            <a class="mmx-btn" href="{{ route('mining-manager.dashboard.details') }}">
                <i class="fas fa-chart-bar"></i> {{ trans('mining-manager::metrics.details') }}
            </a>
        </div>
    </div>

    @if(!($data['configured'] ?? false))
        <div class="mmx-empty">
            {{ trans('mining-manager::metrics.not_configured') }}
            @can('mining-manager.admin')
                <div class="mt-3">
                    <a class="mmx-btn" href="{{ route('mining-manager.settings.index') }}">{{ trans('mining-manager::metrics.open_settings') }}</a>
                </div>
            @endcan
        </div>
    @else

    {{-- Moon ore tiers and their tax --}}
    <div class="mmx-tiers">
        @foreach($data['tiers'] as $tier)
            <div class="mmx-tier mmx-tile" title="{{ trans('mining-manager::metrics.tier_title', ['tier' => $tier['tier']]) }}">
                <div class="mmx-tier-rate">
                    <small>{{ $tier['tier'] }}</small>
                    <strong>{{ $tier['rate'] !== null ? rtrim(rtrim(number_format($tier['rate'], 2, '.', ''), '0'), '.') . '%' : trans('mining-manager::metrics.tier_none') }}</strong>
                </div>
                <div class="mmx-tier-ores">
                    @foreach($tier['ores'] as $ore)
                        <div>{{ $ore }}</div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Period picker: years, then the months of the chosen year --}}
    @php
        $periods = $data['periods'] ?? [];
        $year = (int) $month->format('Y');
        if (!isset($periods[$year])) { $periods[$year] = [(int) $month->format('n')]; krsort($periods); }
        $currentYm = now()->format('Y-m');
    @endphp
    <nav class="mmx-periods" aria-label="Period">
        <div class="mmx-years">
            @foreach($periods as $y => $months)
                @php $target = sprintf('%04d-%02d', $y, max($months)); @endphp
                <a href="{{ $baseUrl . '?' . http_build_query($monthQuery($target)) }}" class="{{ $y === $year ? 'active' : '' }}">{{ $y }}</a>
            @endforeach
        </div>
        <div class="mmx-months">
            @for($m = 1; $m <= 12; $m++)
                @php $ym = sprintf('%04d-%02d', $year, $m); @endphp
                @php $label = \Carbon\Carbon::create($year, $m, 1)->format('M'); @endphp
                @if(in_array($m, $periods[$year] ?? [], true) && $ym <= $currentYm)
                    <a href="{{ $baseUrl . '?' . http_build_query($monthQuery($ym)) }}" class="{{ $m === (int) $month->format('n') ? 'active' : '' }}">{{ $label }}</a>
                @else
                    <span>{{ $label }}</span>
                @endif
            @endfor
        </div>
    </nav>

    {{-- Overview: totals, refinery ring, refinery counts and systems --}}
    <section class="mmx-section mmx-overview">
        <div>
            <div class="mmx-eyebrow">{{ trans('mining-manager::metrics.overview') }}</div>
            <div class="mmx-section-head" style="margin-bottom:0">
                <div>
                    <h2>{{ strtoupper($month->format('F Y')) }}</h2>
                    <p>{!! trans('mining-manager::metrics.overview_text', [
                        'corp' => e($data['corporation']['name']),
                        'from' => e($monthStart->format('j F')),
                        'to' => e($monthEnd->format('j F')),
                    ]) !!}</p>
                </div>
            </div>
            <div class="mmx-tiles">
                <div class="mmx-tile">
                    <span>{{ trans('mining-manager::metrics.isk_mined') }}</span>
                    <strong>{{ $fmt($data['totals']['isk']) }}</strong>
                </div>
                <div class="mmx-tile mmx-tax">
                    <span>{{ trans('mining-manager::metrics.mining_tax') }}</span>
                    <strong>{{ $fmt($data['totals']['tax']) }}</strong>
                </div>
                <div class="mmx-tile">
                    <span>{{ trans('mining-manager::metrics.active_players') }}</span>
                    <strong>{{ $data['totals']['members'] }}@if($data['totals']['guests'] > 0)<small>{{ trans('mining-manager::metrics.guests', ['count' => $data['totals']['guests']]) }}</small>@endif</strong>
                </div>
                <div class="mmx-tile">
                    <span>{{ trans('mining-manager::metrics.volume_mined') }}</span>
                    <strong>{{ $fmt($data['totals']['volume']) }}<small>m³</small></strong>
                </div>
            </div>
        </div>

        <div>
            @if($data['refineries']['online'] > 0)
                <div class="mmx-ring">
                    <canvas id="mmxRing" height="330" aria-label="{{ trans('mining-manager::metrics.refineries') }}"></canvas>
                    <div class="mmx-ring-centre">
                        <strong>{{ $data['refineries']['online'] }}</strong>
                        <span>{{ trans('mining-manager::metrics.ring_label') }}</span>
                    </div>
                </div>
            @else
                <div class="mmx-empty">{{ trans('mining-manager::metrics.no_refineries') }}</div>
            @endif
        </div>

        <div class="mmx-side">
            @php $online = max(1, $data['refineries']['online']); @endphp
            <h3>{{ trans('mining-manager::metrics.refineries') }}</h3>
            <div class="mmx-tiles">
                <div class="mmx-tile mmx-flag">
                    <span>{{ trans('mining-manager::metrics.online') }}</span>
                    <strong>{{ $data['refineries']['online'] }}</strong>
                </div>
                <div class="mmx-tile mmx-flag">
                    <span>{{ trans('mining-manager::metrics.ready') }}</span>
                    <strong>{{ $data['refineries']['ready'] }}<small>{{ round($data['refineries']['ready'] / $online * 100) }}%</small></strong>
                </div>
                <div class="mmx-tile mmx-flag mmx-wait">
                    <span>{{ trans('mining-manager::metrics.extracting') }}</span>
                    <strong>{{ $data['refineries']['extracting'] }}<small>{{ round($data['refineries']['extracting'] / $online * 100) }}%</small></strong>
                </div>
            </div>
            @if($data['refineries']['idle'] > 0)
                <p class="mmx-note">{{ $data['refineries']['idle'] }} {{ strtolower(trans('mining-manager::metrics.idle')) }}</p>
            @endif

            @if(!empty($data['systems']))
                <h3>{{ trans('mining-manager::metrics.systems') }}</h3>
                <div class="mmx-systems">
                    @foreach($data['systems'] as $system => $count)
                        <div><span>{{ $system }}</span><span>{{ $count }}</span></div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @php $hasMining = $data['totals']['isk'] > 0 || $data['totals']['volume'] > 0; @endphp

    {{-- Day by day --}}
    <section class="mmx-section">
        @if($hasMining)
            <div class="mmx-chart"><canvas id="mmxDaily" aria-label="{{ trans('mining-manager::metrics.daily_title') }}"></canvas></div>
        @else
            <div class="mmx-empty">{{ trans('mining-manager::metrics.nothing') }}</div>
        @endif
    </section>

    {{-- Trends --}}
    @php
        $trendLabels = $data['trend']['labels'];
        $trendFrom = \Carbon\Carbon::createFromFormat('Y-m', $trendLabels[0])->format('F Y');
        $trendTo = \Carbon\Carbon::createFromFormat('Y-m', end($trendLabels))->format('F Y');
    @endphp
    <section class="mmx-section">
        <div class="mmx-section-head">
            <div>
                <div class="mmx-eyebrow">{{ trans('mining-manager::metrics.trends') }}</div>
                <h2>{{ trans('mining-manager::metrics.trend_title', ['count' => $data['trendMonths']]) }}</h2>
                <p>{!! trans('mining-manager::metrics.trend_text', [
                    'corp' => e($data['corporation']['name']),
                    'from' => e($trendFrom),
                    'to' => e($trendTo),
                ]) !!}</p>
            </div>
            <div class="mmx-controls">
                <div class="mmx-years" style="border:0;padding:0">
                    @foreach(\MiningManager\Services\Metrics\MiningMetricsService::TREND_CHOICES as $choice)
                        <a href="{{ $baseUrl . '?' . http_build_query(['month' => $month->format('Y-m')] + ($choice !== 12 ? ['months' => $choice] : [])) }}"
                           class="{{ $choice === $data['trendMonths'] ? 'active' : '' }}">{{ $choice }}</a>
                    @endforeach
                </div>
                <button type="button" class="mmx-toggle" data-series="volume"><span class="mmx-swatch mmx-vol"></span>{{ trans('mining-manager::metrics.volume') }}</button>
                <button type="button" class="mmx-toggle active" data-series="isk"><span class="mmx-swatch mmx-isk"></span>{{ trans('mining-manager::metrics.isk_mined') }}</button>
                <button type="button" class="mmx-toggle active" data-series="tax"><span class="mmx-swatch mmx-taxc"></span>{{ trans('mining-manager::metrics.mining_tax') }}</button>
            </div>
        </div>
        <div class="mmx-chart mmx-chart-tall"><canvas id="mmxTrend" aria-label="{{ trans('mining-manager::metrics.trends') }}"></canvas></div>
    </section>

    {{-- Refineries --}}
    @if(!empty($data['structures']))
    @php $activeStructures = count(array_filter($data['structures'], fn ($s) => $s['active'])); @endphp
    <section class="mmx-section" id="mmx-structures">
        <div class="mmx-section-head">
            <div>
                <div class="mmx-eyebrow">{{ trans('mining-manager::metrics.structures') }}</div>
                <h2>{{ strtoupper($month->format('F Y')) }}</h2>
                <p>{{ trans('mining-manager::metrics.structures_text', ['corp' => $data['corporation']['name']]) }}</p>
            </div>
            <div class="mmx-controls">
                <span class="mmx-count">{{ trans('mining-manager::metrics.results') }}<strong data-count>{{ $activeStructures }} / {{ count($data['structures']) }}</strong></span>
                <input type="search" class="mmx-search" data-search placeholder="{{ trans('mining-manager::metrics.search_system') }}">
                <div class="w-100"></div>
                <button type="button" class="mmx-toggle active" data-tier="all"><span class="mmx-swatch mmx-all"></span>{{ trans('mining-manager::metrics.all') }}</button>
                @foreach(['R4', 'R8', 'R16', 'R32', 'R64'] as $t)
                    @if(collect($data['structures'])->contains('rarity', $t))
                        <button type="button" class="mmx-toggle" data-tier="{{ $t }}"><span class="mmx-swatch {{ $tierClass($t) }}"></span>{{ $t }}</button>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="mmx-cards">
            @foreach($data['structures'] as $s)
                <div class="mmx-card {{ $s['active'] ? '' : 'mmx-quiet' }}"
                     data-tier="{{ $s['rarity'] ?? '' }}"
                     data-active="{{ $s['active'] ? 1 : 0 }}"
                     data-text="{{ strtolower(($s['system'] ?? '') . ' ' . $s['name']) }}"
                     title="{{ $s['name'] }}&#10;{{ trans('mining-manager::metrics.isk_mined') }}: {{ $fmt($s['isk']) }}&#10;{{ trans('mining-manager::metrics.mining_tax') }}: {{ $fmt($s['tax']) }}&#10;{{ trans('mining-manager::metrics.volume') }}: {{ $fmt($s['volume']) }} m³@if($s['arrival'])&#10;{{ trans('mining-manager::metrics.chunk', ['date' => \Carbon\Carbon::parse($s['arrival'])->format('j M')]) }}@endif">
                    <img src="https://images.evetech.net/types/{{ $s['type_id'] ?: 35835 }}/render?size=64" alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                    <div class="mmx-card-body">
                        <div class="mmx-card-name">{{ $s['name'] }}</div>
                        <div class="mmx-card-row">
                            <span class="mmx-card-big {{ $tierClass($s['rarity']) }}">{{ $s['rarity'] ?? '–' }}</span>
                            <span class="mmx-card-meta">{{ $s['system'] ?? '' }}<i>|</i>{{ $fmt($s['isk'], 1) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Players --}}
    @if(!empty($data['players']))
    <section class="mmx-section" id="mmx-players">
        <div class="mmx-section-head">
            <div>
                <div class="mmx-eyebrow">{{ trans('mining-manager::metrics.players') }}</div>
                <h2>{{ strtoupper($month->format('F Y')) }}</h2>
                <p>{{ trans('mining-manager::metrics.players_text', ['corp' => $data['corporation']['name']]) }}</p>
            </div>
            <div class="mmx-controls">
                <span class="mmx-count">{{ trans('mining-manager::metrics.results') }}<strong data-count>{{ count($data['players']) }}</strong></span>
                <input type="search" class="mmx-search" data-search placeholder="{{ trans('mining-manager::metrics.search_player') }}">
                <div class="w-100"></div>
                <button type="button" class="mmx-toggle active" data-sort="volume"><span class="mmx-swatch mmx-vol"></span>{{ trans('mining-manager::metrics.volume') }}</button>
                @if($canSeeIsk)
                    <button type="button" class="mmx-toggle" data-sort="isk"><span class="mmx-swatch mmx-isk"></span>{{ trans('mining-manager::metrics.isk_mined') }}</button>
                    <button type="button" class="mmx-toggle" data-sort="tax"><span class="mmx-swatch mmx-taxc"></span>{{ trans('mining-manager::metrics.mining_tax') }}</button>
                @endif
            </div>
        </div>
        <div class="mmx-cards" data-sortable>
            @foreach($data['players'] as $p)
                <div class="mmx-card {{ !empty($p['own']) ? 'mmx-own' : '' }}"
                     data-text="{{ strtolower($p['name']) }}"
                     data-volume="{{ $p['volume'] }}"
                     data-isk="{{ $p['isk'] ?? 0 }}"
                     data-tax="{{ $p['tax'] ?? 0 }}">
                    <img src="{{ $p['portrait'] }}" alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                    <div class="mmx-card-body">
                        <div class="mmx-card-name">
                            {{ $p['name'] }}
                            @if($p['guest'])<span class="mmx-tag">{{ trans('mining-manager::metrics.guest') }}</span>@endif
                        </div>
                        <div class="mmx-card-row">
                            <span class="mmx-card-big mmx-vol" data-big
                                  data-volume-text="{{ $fmt($p['volume'], 0) }}"
                                  data-isk-text="{{ $p['isk'] !== null ? $fmt($p['isk']) : '' }}"
                                  data-tax-text="{{ $p['tax'] !== null ? $fmt($p['tax']) : '' }}">{{ $fmt($p['volume'], 0) }}</span>
                            <span class="mmx-card-meta">
                                @if($p['isk'] !== null)
                                    <span class="mmx-isk">{{ $fmt($p['isk'], 1) }}</span><i>|</i><span class="mmx-taxc">{{ $fmt($p['tax'], 1) }}</span>
                                @elseif($p['characters'] > 1)
                                    {{ trans('mining-manager::metrics.alts', ['count' => $p['characters']]) }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @unless($canSeeIsk)
            <p class="mmx-note">{{ trans('mining-manager::metrics.isk_hidden') }}</p>
        @endunless
    </section>
    @endif

    @endif
</div>
@endsection

@push('javascript')
@if($data['configured'] ?? false)
<script src="{{ asset('vendor/mining-manager/js/vendor/chart.min.js') }}"></script>
@php
    // Built here, not inside @json(): @json splits its argument on commas.
    $mmRing = [
        'ready' => $data['refineries']['ready'],
        'extracting' => $data['refineries']['extracting'],
        'idle' => $data['refineries']['idle'],
        'labels' => [
            trans('mining-manager::metrics.ready'),
            trans('mining-manager::metrics.extracting'),
            trans('mining-manager::metrics.idle'),
        ],
    ];
    $mmLabels = [
        'isk' => trans('mining-manager::metrics.isk_mined'),
        'tax' => trans('mining-manager::metrics.mining_tax'),
        'volume' => trans('mining-manager::metrics.volume') . ' (m³)',
    ];
@endphp
<script>
window.mmMetrics = {
    ring: @json($mmRing),
    daily: @json($data['daily']),
    trend: @json($data['trend']),
    labels: @json($mmLabels)
};
</script>
<script src="{{ \MiningManager\Support\Asset::url('js/mm-metrics.js') }}"></script>
@endif
@endpush
