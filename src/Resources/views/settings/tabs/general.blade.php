<form method="POST" action="{{ route('mining-manager.settings.update-general') }}">
    @csrf

    {{-- Hidden field to track selected corporation context --}}
    <input type="hidden" name="selected_corporation_id" id="selected_corporation_id" value="{{ $selectedCorporationId ?? '' }}">

    <h4>
        <i class="fas fa-sliders-h"></i>
        {{ trans('mining-manager::settings.general_settings') }}
        <span class="badge badge-success ml-2">Global</span>
    </h4>
    <hr>

    <div class="alert alert-success">
        <i class="fas fa-globe"></i>
        <strong>Global Settings</strong> - These settings apply to ALL corporations and cannot be overridden per-corporation.
    </div>

    {{-- Primary Corporation Setup --}}
    <div class="card bg-dark mb-3 border-primary">
        <div class="card-header bg-primary">
            <h5 class="card-title mb-0">
                <i class="fas fa-building"></i>
                Primary Corporation Setup
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i>
                <strong>Important:</strong> Set up your primary corporation that owns moons and structures.
                This determines which moons and refineries are tracked.
            </div>

            <div class="row">
                <div class="col-md-6">
                    {{-- Moon Owner Corporation --}}
                    <div class="form-group">
                        <label for="moon_owner_corporation_id">
                            <i class="fas fa-moon"></i>
                            Moon/Structure Owner Corporation <span class="text-danger">*</span>
                        </label>
                        <select class="form-control @error('moon_owner_corporation_id') is-invalid @enderror"
                                id="moon_owner_corporation_id"
                                name="moon_owner_corporation_id"
                                required>
                            <option value="">-- Select Corporation --</option>
                            @if(isset($corporations))
                                @foreach($corporations as $corp)
                                    <option value="{{ $corp->corporation_id }}"
                                        {{ (old('moon_owner_corporation_id', $settings->moon_owner_corporation_id ?? '') == $corp->corporation_id) ? 'selected' : '' }}>
                                        [{{ $corp->ticker }}] {{ $corp->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('moon_owner_corporation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            The corporation that owns your moons/structures. Moon tracking, ledger data and webhook notifications are scoped to this corporation, regardless of ore source (moon, belt, ice, gas).
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    {{-- Currently Configured --}}
                    @if(!empty($settings->moon_owner_corporation_id))
                    <div class="form-group">
                        <label><i class="fas fa-check-circle text-success"></i> Currently Active</label>
                        <div class="alert alert-success mb-0">
                            @php
                                $moonOwnerCorp = $corporations->firstWhere('corporation_id', $settings->moon_owner_corporation_id);
                            @endphp
                            @if($moonOwnerCorp)
                                <span class="h5">[{{ $moonOwnerCorp->ticker }}] {{ $moonOwnerCorp->name }}</span>
                                <br>
                                <small class="text-muted">ID: {{ $moonOwnerCorp->corporation_id }}</small>
                            @else
                                <span class="text-warning">ID: {{ $settings->moon_owner_corporation_id }} (Not found)</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Hidden fields for corporation name and ticker (auto-filled by controller) --}}
            <input type="hidden" name="corporation_name" value="{{ old('corporation_name', $settings->corporation_name ?? '') }}">
            <input type="hidden" name="corporation_ticker" value="{{ old('corporation_ticker', $settings->corporation_ticker ?? '') }}">
        </div>
    </div>

    {{-- Time & Date Information --}}
    <div class="card bg-dark mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-clock"></i>
                Time & Date Settings
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <h6><i class="fas fa-info-circle"></i> UTC (EVE Time) is Always Used</h6>
                <p class="mb-2">
                    All dates and months use <strong>UTC (EVE Time)</strong> exclusively to ensure consistency with:
                </p>
                <ul class="mb-0">
                    <li>Corporation mining ledger timestamps from the EVE API</li>
                    <li>Month boundaries aligned with EVE's calendar</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Display Settings --}}
    <div class="card bg-dark mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-eye"></i>
                {{ trans('mining-manager::settings.display_settings') }}
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="items_per_page">
                            <i class="fas fa-list"></i>
                            {{ trans('mining-manager::settings.items_per_page') }}
                        </label>
                        <select class="form-control @error('items_per_page') is-invalid @enderror"
                                id="items_per_page"
                                name="items_per_page">
                            <option value="10" {{ ($settings->items_per_page ?? 25) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ ($settings->items_per_page ?? 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ ($settings->items_per_page ?? 25) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ ($settings->items_per_page ?? 25) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        @error('items_per_page')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            {{ trans('mining-manager::settings.items_per_page_help') }}
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="currency_decimals">
                            <i class="fas fa-calculator"></i>
                            {{ trans('mining-manager::settings.currency_decimals') }}
                        </label>
                        <select class="form-control @error('currency_decimals') is-invalid @enderror"
                                id="currency_decimals"
                                name="currency_decimals">
                            <option value="0" {{ ($settings->currency_decimals ?? 2) == 0 ? 'selected' : '' }}>
                                0 (1,234,567)
                            </option>
                            <option value="2" {{ ($settings->currency_decimals ?? 2) == 2 ? 'selected' : '' }}>
                                2 (1,234,567.89)
                            </option>
                            <option value="4" {{ ($settings->currency_decimals ?? 2) == 4 ? 'selected' : '' }}>
                                4 (1,234,567.8901)
                            </option>
                        </select>
                        @error('currency_decimals')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            {{ trans('mining-manager::settings.currency_decimals_help') }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="custom-control custom-switch">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="show_character_portraits"
                               name="show_character_portraits"
                               value="1"
                               {{ old('show_character_portraits', $settings->show_character_portraits ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="show_character_portraits">
                            <i class="fas fa-user-circle"></i>
                            {{ trans('mining-manager::settings.show_character_portraits') }}
                        </label>
                        <small class="form-text text-muted">
                            {{ trans('mining-manager::settings.show_character_portraits_help') }}
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="custom-control custom-switch">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="compact_mode"
                               name="compact_mode"
                               value="1"
                               {{ old('compact_mode', $settings->compact_mode ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="compact_mode">
                            <i class="fas fa-compress"></i>
                            {{ trans('mining-manager::settings.compact_mode') }}
                        </label>
                        <small class="form-text text-muted">
                            {{ trans('mining-manager::settings.compact_mode_help') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Settings (Moved) --}}
    <div class="card bg-dark mb-3">
        <div class="card-body text-center text-muted py-4">
            <i class="fas fa-bell fa-2x mb-2 d-block"></i>
            <p class="mb-0">Notification settings have moved to the dedicated
            <a href="#" onclick="$('.nav-link[data-tab=notifications]').click(); return false;">
                <i class="fas fa-bell"></i> Notifications tab</a>.</p>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="action-buttons">
        <div class="row">
            <div class="col-md-6">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-save"></i>
                    {{ trans('mining-manager::settings.save_changes') }}
                </button>
            </div>
            <div class="col-md-6">
                <a href="{{ route('mining-manager.settings.index') }}"
                   class="btn btn-secondary btn-block">
                    <i class="fas fa-undo"></i>
                    {{ trans('mining-manager::settings.reset_form') }}
                </a>
            </div>
        </div>
    </div>

</form>
