{{--
    Ore owed and ore paid side by side, by ore type (OreTally).
    Expects $owed (ore still to hand in; null leaves the side out) and
    $paid (OreTally::paidBy). Each line: type_id, name, units, isk, and
    volume on the owed side.
--}}
@php
    $paidLines = $paid['lines'] ?? [];
@endphp
<div class="row mm-ore-tally">
    @if($owed !== null)
    <div class="col-lg-6 mb-3 mb-lg-0">
        <h6 class="text-warning mb-2">
            <i class="fas fa-hourglass-half"></i> {{ trans('mining-manager::taxes.ore_tally_owed') }}
        </h6>
        @if(empty($owed))
            <p class="text-muted small mb-0">{{ trans('mining-manager::taxes.ore_tally_owed_none') }}</p>
        @else
            <table class="table table-sm table-dark mb-0">
                <thead>
                    <tr>
                        <th>{{ trans('mining-manager::taxes.ore_col_ore') }}</th>
                        <th class="text-right">{{ trans('mining-manager::taxes.ore_col_units') }}</th>
                        <th class="text-right">{{ trans('mining-manager::taxes.ore_col_volume') }}</th>
                        <th class="text-right">{{ trans('mining-manager::taxes.ore_col_worth') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($owed as $line)
                        <tr>
                            <td>
                                <img src="https://images.evetech.net/types/{{ $line['type_id'] }}/icon?size=32" alt="" width="20" height="20" class="mr-1" loading="lazy" onerror="this.style.display='none'">
                                {{ $line['name'] }}
                            </td>
                            <td class="text-right"><strong>{{ number_format($line['units']) }}</strong></td>
                            <td class="text-right text-muted">{{ number_format($line['volume'] ?? 0, 0) }} m³</td>
                            <td class="text-right text-muted">{{ \MiningManager\Support\IskFormat::short($line['isk'] ?? 0) }} ISK</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ trans('mining-manager::taxes.ore_total') }}</th>
                        <th></th>
                        <th class="text-right">{{ number_format(array_sum(array_column($owed, 'volume')), 0) }} m³</th>
                        <th class="text-right">{{ \MiningManager\Support\IskFormat::short(array_sum(array_column($owed, 'isk'))) }} ISK</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
    @endif

    <div class="{{ $owed !== null ? 'col-lg-6' : 'col-12' }}">
        <h6 class="text-success mb-2">
            <i class="fas fa-check-circle"></i> {{ trans('mining-manager::taxes.ore_tally_paid') }}
            @if(($paid['contracts'] ?? 0) > 0)
                <small class="text-muted">&middot; {{ trans_choice('mining-manager::taxes.ore_tally_contracts', $paid['contracts'], ['count' => $paid['contracts']]) }}</small>
            @endif
        </h6>
        @if(empty($paidLines))
            <p class="text-muted small mb-0">{{ trans('mining-manager::taxes.ore_tally_paid_none') }}</p>
        @else
            <table class="table table-sm table-dark mb-0">
                <thead>
                    <tr>
                        <th>{{ trans('mining-manager::taxes.ore_col_ore') }}</th>
                        <th class="text-right">{{ trans('mining-manager::taxes.ore_col_units_paid') }}</th>
                        <th class="text-right">{{ trans('mining-manager::taxes.ore_col_worth') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paidLines as $line)
                        <tr>
                            <td>
                                <img src="https://images.evetech.net/types/{{ $line['type_id'] }}/icon?size=32" alt="" width="20" height="20" class="mr-1" loading="lazy" onerror="this.style.display='none'">
                                {{ $line['name'] }}
                            </td>
                            <td class="text-right"><strong>{{ number_format($line['units']) }}</strong></td>
                            <td class="text-right text-muted">{{ \MiningManager\Support\IskFormat::short($line['isk'] ?? 0) }} ISK</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ trans('mining-manager::taxes.ore_total') }}</th>
                        <th></th>
                        <th class="text-right">{{ \MiningManager\Support\IskFormat::short(array_sum(array_column($paidLines, 'isk'))) }} ISK</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>
