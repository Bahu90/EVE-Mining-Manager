<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ trans('mining-manager::ledger.entry_details') }}</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.date') }}:</dt>
                    <dd class="col-sm-8">{{ \Carbon\Carbon::parse($entry->date)->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.character') }}:</dt>
                    <dd class="col-sm-8">
                        <img src="https://images.evetech.net/characters/{{ $entry->character_id }}/portrait?size=32"
                             class="img-circle" style="width: 24px; height: 24px;">
                        {{ $entry->character_name }}
                    </dd>

                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.ore_type') }}:</dt>
                    <dd class="col-sm-8">
                        @if($entry->type)
                            <img src="https://images.evetech.net/types/{{ $entry->type_id }}/icon?size=32" 
                                 style="width: 24px; height: 24px;">
                            {{ $entry->type->typeName }}
                        @else
                            {{ trans('mining-manager::ledger.unknown') }}
                        @endif
                    </dd>

                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.solar_system') }}:</dt>
                    <dd class="col-sm-8">
                        @if($entry->solarSystem)
                            {{ $entry->solarSystem->name }}
                        @else
                            {{ trans('mining-manager::ledger.unknown') }}
                        @endif
                    </dd>
                </dl>
            </div>

            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.quantity') }}:</dt>
                    <dd class="col-sm-8">{{ number_format($entry->quantity, 2) }}</dd>

                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.price') }}:</dt>
                    <dd class="col-sm-8">{{ number_format($entry->price, 2) }} ISK</dd>

                    <dt class="col-sm-4">{{ trans('mining-manager::ledger.total_value') }}:</dt>
                    <dd class="col-sm-8"><strong>{{ number_format($entry->total_value, 2) }} ISK</strong></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
