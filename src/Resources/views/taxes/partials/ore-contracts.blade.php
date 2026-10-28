{{--
    Ore tax contracts: what was handed in, and what became of it.
    Expects $contracts (OreContract collection). $showIssuer adds the pilot.
--}}
@php
    $statusBadge = [
        \MiningManager\Models\OreContract::WAITING => 'warning',
        \MiningManager\Models\OreContract::CREDITED => 'success',
        \MiningManager\Models\OreContract::REFUSED => 'secondary',
        \MiningManager\Models\OreContract::EMPTY => 'danger',
        \MiningManager\Models\OreContract::IGNORED => 'secondary',
    ];
    $issuerNames = !empty($showIssuer)
        ? app(\MiningManager\Services\Character\CharacterNames::class)->many($contracts->pluck('issuer_id')->all())
        : [];
@endphp
<div class="table-responsive">
    <table class="table table-sm table-dark mb-0">
        <thead>
            <tr>
                <th>{{ trans('mining-manager::taxes.ore_contract_made') }}</th>
                @if(!empty($showIssuer))<th>{{ trans('mining-manager::taxes.ore_contract_pilot') }}</th>@endif
                <th>{{ trans('mining-manager::taxes.ore_contract_status') }}</th>
                <th>{{ trans('mining-manager::taxes.ore_contract_ore') }}</th>
                <th class="text-right">{{ trans('mining-manager::taxes.ore_col_worth') }}</th>
                <th>{{ trans('mining-manager::taxes.ore_contract_went') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $contract)
                <tr>
                    <td>
                        {{ optional($contract->date_issued)->format('M d, H:i') }}
                        <br><small class="text-muted">#{{ $contract->contract_id }}</small>
                    </td>
                    @if(!empty($showIssuer))
                        <td>{{ $issuerNames[$contract->issuer_id]['name'] ?? $contract->issuer_id }}</td>
                    @endif
                    <td>
                        <span class="badge badge-{{ $statusBadge[$contract->status] ?? 'secondary' }}">
                            {{ trans('mining-manager::taxes.ore_status_' . $contract->status) }}
                        </span>
                        @if($contract->notes)
                            <br><small class="text-muted">{{ $contract->notes }}</small>
                        @endif
                    </td>
                    <td>
                        @forelse(array_slice($contract->items ?? [], 0, 4) as $item)
                            <div class="small">
                                {{ number_format($item['units'] ?? 0) }} {{ $item['name'] ?? ('Type ' . ($item['type_id'] ?? '?')) }}
                                @if(empty($item['on_bill']))
                                    <span class="text-muted">({{ trans('mining-manager::taxes.ore_not_on_bill') }})</span>
                                @endif
                            </div>
                        @empty
                            <span class="text-muted small">&mdash;</span>
                        @endforelse
                        @if(count($contract->items ?? []) > 4)
                            <div class="small text-muted">+{{ count($contract->items) - 4 }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($contract->value, 0) }} ISK</td>
                    <td>
                        @if($contract->status === \MiningManager\Models\OreContract::CREDITED)
                            <small>
                                {{ trans('mining-manager::taxes.ore_to_bills', ['amount' => number_format($contract->credited, 0)]) }}
                                @if($contract->held > 0)
                                    <br>{{ trans('mining-manager::taxes.ore_to_balance', ['amount' => number_format($contract->held, 0)]) }}
                                @endif
                            </small>
                        @elseif($contract->status === \MiningManager\Models\OreContract::WAITING)
                            <small class="text-muted">{{ trans('mining-manager::taxes.ore_waiting_accept') }}</small>
                        @else
                            <small class="text-muted">&mdash;</small>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
