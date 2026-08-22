@php
    /**
     * Leave balance of one employee.
     *
     * Required: $summary (App\Services\HumanResources\LeaveBalanceService::summaryFor)
     *           and $balanceUserId.
     * Optional: $balanceEditable — shows the entitlement forms (HR only).
     */
    $balanceEditable = $balanceEditable ?? false;
@endphp

<div class="table-responsive p-0">
    <table class="table table-hover table-sm">
        <thead>
            <tr>
                <th>{{ __('general_content.leave_type_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_entitled_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_carried_over_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_adjustment_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_acquired_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_taken_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_pending_trans_key') }}</th>
                <th class="text-right">{{ __('general_content.leave_remaining_trans_key') }}</th>
                @if($balanceEditable)<th></th>@endif
            </tr>
        </thead>
        <tbody>
        @forelse ($summary['lines'] as $line)
            <tr>
                <td>
                    <span class="badge" style="background-color: {{ $line['type']->color ?? '#6c757d' }}">&nbsp;</span>
                    {{ $line['type']->label }}
                    @unless($line['type']->counts_against_balance)
                        <small class="text-muted">({{ __('general_content.leave_not_counted_trans_key') }})</small>
                    @endunless
                </td>
                <td class="text-right">{{ number_format($line['entitled'], 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($line['carried_over'], 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($line['adjustment'], 2, ',', ' ') }}</td>
                <td class="text-right"><strong>{{ number_format($line['acquired'], 2, ',', ' ') }}</strong></td>
                <td class="text-right">{{ number_format($line['taken'], 2, ',', ' ') }}</td>
                <td class="text-right">
                    @if($line['pending'] > 0)
                        <span class="text-warning">{{ number_format($line['pending'], 2, ',', ' ') }}</span>
                    @else
                        {{ number_format($line['pending'], 2, ',', ' ') }}
                    @endif
                </td>
                <td class="text-right">
                    @if($line['remaining'] === null)
                        <span class="text-muted">—</span>
                    @else
                        <strong class="{{ $line['remaining'] < 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($line['remaining'], 2, ',', ' ') }}
                        </strong>
                    @endif
                </td>
                @if($balanceEditable)
                    <td class="py-0 align-middle">
                        <x-ButtonTextEdit :modalTarget="'LeaveBalance' . $balanceUserId . '_' . $line['type']->id" />
                        <x-adminlte-modal id="LeaveBalance{{ $balanceUserId }}_{{ $line['type']->id }}" title="{{ $line['type']->label }} — {{ $summary['period_label'] }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('human.resources.leave.balance.store') }}">
                                @csrf
                                <div class="card-body">
                                    <input type="hidden" name="user_id" value="{{ $balanceUserId }}">
                                    <input type="hidden" name="leave_type_id" value="{{ $line['type']->id }}">
                                    <input type="hidden" name="period_start" value="{{ $summary['period_start']->toDateString() }}">
                                    <div class="form-group">
                                        <label for="entitled_days">{{ __('general_content.leave_entitled_trans_key') }}</label>
                                        <input type="number" step="0.5" min="0" class="form-control" name="entitled_days" value="{{ $line['entitled'] }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="carried_over_days">{{ __('general_content.leave_carried_over_trans_key') }}</label>
                                        <input type="number" step="0.5" class="form-control" name="carried_over_days" value="{{ $line['carried_over'] }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="adjustment_days">{{ __('general_content.leave_adjustment_trans_key') }}</label>
                                        <input type="number" step="0.5" class="form-control" name="adjustment_days" value="{{ $line['adjustment'] }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
                                        <input type="text" class="form-control" name="comment" value="{{ $line['balance']->comment ?? '' }}">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-modal>
                    </td>
                @endif
            </tr>
        @empty
            <x-EmptyDataLine col="{{ $balanceEditable ? 9 : 8 }}" text="{{ __('general_content.no_data_trans_key') }}" />
        @endforelse
        </tbody>
    </table>
</div>
<small class="text-muted">
    {{ __('general_content.leave_period_trans_key') }} :
    {{ $summary['period_start']->format('d/m/Y') }} → {{ $summary['period_end']->format('d/m/Y') }}
</small>
