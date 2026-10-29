{{--
    How to pay a bill in ore: the ore it comes to and how to hand it over.
    Expects $bill (MiningTax), $lines (OreTaxService::linesForBill), $corpName,
    and optionally $taxCode (the bill's active TaxCode).
--}}
@php
    $oreIsk = array_sum(array_column($lines, 'isk'));
    $oreVolume = array_sum(array_column($lines, 'volume'));
    $useCodes = $useCodes ?? \MiningManager\Support\TaxCodes::enabled();
    $fullCode = $useCodes && isset($taxCode) && $taxCode ? $taxCode->getFullCode() : null;
@endphp
<div class="card bg-dark border-info mt-2 mb-2 mm-ore-pay">
    <div class="card-header bg-dark border-info">
        <h6 class="mb-0"><i class="fas fa-cubes"></i> {{ trans('mining-manager::taxes.ore_pay_title') }}</h6>
    </div>
    <div class="card-body">
        @if(empty($lines))
            <p class="mb-0 text-muted">{{ trans('mining-manager::taxes.ore_pay_none') }}</p>
        @else
            <p class="mb-2">{{ trans('mining-manager::taxes.ore_pay_intro', ['amount' => number_format($bill->getRemainingBalance(), 0)]) }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-dark mb-3">
                    <thead>
                        <tr>
                            <th>{{ trans('mining-manager::taxes.ore_col_ore') }}</th>
                            <th class="text-right">{{ trans('mining-manager::taxes.ore_col_units') }}</th>
                            <th class="text-right">{{ trans('mining-manager::taxes.ore_col_volume') }}</th>
                            <th class="text-right">{{ trans('mining-manager::taxes.ore_col_worth') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                            <tr>
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $line['type_id'] }}/icon?size=32" alt="" width="20" height="20" class="mr-1" loading="lazy" onerror="this.style.display='none'">
                                    {{ $line['name'] }}
                                </td>
                                <td class="text-right">
                                    <strong>{{ number_format($line['hand_in']) }}</strong>
                                    <button type="button" class="btn btn-xs btn-outline-primary ml-1" onclick="copyToClipboard('{{ (int) $line['hand_in'] }}', {{ \Illuminate\Support\Js::from($line['name']) }})" title="{{ trans('mining-manager::taxes.copy') }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td class="text-right text-muted">{{ number_format($line['volume'], 0) }} m³</td>
                                <td class="text-right text-muted">{{ number_format($line['isk'], 0) }} ISK</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ trans('mining-manager::taxes.ore_total') }}</th>
                            <th></th>
                            <th class="text-right">{{ number_format($oreVolume, 0) }} m³</th>
                            <th class="text-right">{{ number_format($oreIsk, 0) }} ISK</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <ol class="mb-0">
            <li class="mb-2">{!! trans('mining-manager::taxes.ore_step_create') !!}</li>
            <li class="mb-2">{!! trans('mining-manager::taxes.ore_step_assign', ['corp' => e($corpName ?? trans('mining-manager::taxes.your_corporation'))]) !!}</li>
            <li class="mb-2">{!! trans('mining-manager::taxes.ore_step_items') !!}</li>
            @if($useCodes)
            <li class="mb-2">
                {!! trans('mining-manager::taxes.ore_step_code') !!}
                <br>
                <code class="text-warning" style="font-size: 1.1em;">{{ $fullCode ?? trans('mining-manager::taxes.code_pending') }}</code>
                @if($fullCode)
                    <button type="button" class="btn btn-xs btn-outline-primary ml-2" onclick="copyToClipboard('{{ $fullCode }}', 'Tax code')">
                        <i class="fas fa-copy"></i> {{ trans('mining-manager::taxes.copy') }}
                    </button>
                @endif
            </li>
            @endif
            <li class="mb-0">{{ trans('mining-manager::taxes.ore_step_credit') }}</li>
        </ol>
    </div>
</div>
