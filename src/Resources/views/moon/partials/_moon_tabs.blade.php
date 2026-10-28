{{-- One set of tabs for every moon page: extractions, planning and the drills.
     Tabs someone cannot open are left out. The current tab comes from the
     route, so a page needs to pass nothing. --}}
@php
    $mmMoonTab = fn (string ...$routes) => request()->routeIs(...$routes) ? 'active' : '';
@endphp
<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.index', 'mining-manager.moon.extractions', 'mining-manager.moon.show') }}" href="{{ route('mining-manager.moon.index') }}">
            <i class="fas fa-list"></i> {{ trans('mining-manager::menu.all_extractions') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.active') }}" href="{{ route('mining-manager.moon.active') }}">
            <i class="fas fa-hourglass-half"></i> {{ trans('mining-manager::menu.active_extractions') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.calendar') }}" href="{{ route('mining-manager.moon.calendar') }}">
            <i class="fas fa-calendar-alt"></i> {{ trans('mining-manager::menu.extraction_calendar') }}
        </a>
    </li>
    @if(auth()->user()->canAny(\MiningManager\Http\Controllers\MoonPlannerController::PLANNER_ABILITIES) || \MiningManager\Support\Access::readsCorporation())
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.planner') }}" href="{{ route('mining-manager.moon.planner') }}">
            <i class="fas fa-calendar-check"></i> {{ trans('mining-manager::menu.moon_planner') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.blueprints') }}" href="{{ route('mining-manager.moon.blueprints') }}">
            <i class="fas fa-drafting-compass"></i> {{ trans('mining-manager::menu.moon_blueprints') }}
        </a>
    </li>
    @endif
    @canany(\MiningManager\Http\Controllers\MoonController::SIMULATOR_ABILITIES)
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.calculator') }}" href="{{ route('mining-manager.moon.calculator') }}">
            <i class="fas fa-flask"></i> {{ trans('mining-manager::menu.moon_value_calculator') }}
        </a>
    </li>
    @endcanany
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.compositions') }}" href="{{ route('mining-manager.moon.compositions') }}">
            <i class="fas fa-chart-bar"></i> {{ trans('mining-manager::menu.moon_compositions') }}
        </a>
    </li>
    @can('mining-manager.reads_corporation')
    <li class="nav-item">
        <a class="nav-link {{ $mmMoonTab('mining-manager.moon.metenox-cargo') }}" href="{{ route('mining-manager.moon.metenox-cargo') }}">
            <i class="fas fa-box-open"></i> {{ trans('mining-manager::menu.metenox_cargo') }}
        </a>
    </li>
    @endcan
</ul>
