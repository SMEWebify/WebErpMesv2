<div class="row">
    <div class="col-md-7">
        <x-adminlte-card title="{{ __('general_content.leave_types_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.code_trans_key') }}</th>
                            <th>{{ __('general_content.label_trans_key') }}</th>
                            <th class="text-right">{{ __('general_content.leave_default_quota_trans_key') }}</th>
                            <th>{{ __('general_content.leave_counts_against_balance_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($LeaveTypes as $LeaveType)
                        <tr>
                            <td>
                                <span class="badge" style="background-color: {{ $LeaveType->color ?? '#6c757d' }}">&nbsp;</span>
                                {{ $LeaveType->code }}
                            </td>
                            <td>{{ $LeaveType->label }}</td>
                            <td class="text-right">{{ number_format((float) $LeaveType->default_annual_quota, 2, ',', ' ') }}</td>
                            <td>
                                @if($LeaveType->counts_against_balance)
                                    <i class="fas fa-check text-success"></i>
                                @else
                                    <i class="fas fa-minus text-muted"></i>
                                @endif
                            </td>
                            <td>
                                @if($LeaveType->active)
                                    {{ __('general_content.active_trans_key') }}
                                @else
                                    <span class="text-muted">{{ __('general_content.inactive_trans_key') }}</span>
                                @endif
                            </td>
                            <td class="py-0 align-middle">
                                <x-ButtonTextEdit :modalTarget="'LeaveType' . $LeaveType->id" />
                                <x-adminlte-modal id="LeaveType{{ $LeaveType->id }}" title="{{ $LeaveType->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('human.resources.leave.type.update', ['id' => $LeaveType->id]) }}">
                                        @csrf
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>{{ __('general_content.code_trans_key') }}</label>
                                                <input type="text" class="form-control" name="code" maxlength="20" value="{{ $LeaveType->code }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('general_content.label_trans_key') }}</label>
                                                <input type="text" class="form-control" name="label" value="{{ $LeaveType->label }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('general_content.color_trans_key') }}</label>
                                                <input type="color" class="form-control" name="color" value="{{ $LeaveType->color ?? '#6c757d' }}">
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('general_content.leave_default_quota_trans_key') }}</label>
                                                <input type="number" step="0.5" min="0" class="form-control" name="default_annual_quota" value="{{ $LeaveType->default_annual_quota }}">
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('general_content.ordre_trans_key') }}</label>
                                                <input type="number" min="0" class="form-control" name="ordre" value="{{ $LeaveType->ordre }}">
                                            </div>
                                            <div class="form-check">
                                                <input type="hidden" name="counts_against_balance" value="0">
                                                <input type="checkbox" class="form-check-input" name="counts_against_balance" value="1" id="counts{{ $LeaveType->id }}" @if($LeaveType->counts_against_balance) checked @endif>
                                                <label class="form-check-label" for="counts{{ $LeaveType->id }}">{{ __('general_content.leave_counts_against_balance_trans_key') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="hidden" name="active" value="0">
                                                <input type="checkbox" class="form-check-input" name="active" value="1" id="active{{ $LeaveType->id }}" @if($LeaveType->active) checked @endif>
                                                <label class="form-check-label" for="active{{ $LeaveType->id }}">{{ __('general_content.active_trans_key') }}</label>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                        </div>
                                    </form>
                                </x-adminlte-modal>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('human.resources.leave.balances') }}" class="btn btn-flat btn-primary">
                <i class="fas fa-calendar-check"></i> {{ __('general_content.leave_balances_trans_key') }}
            </a>
        </x-adminlte-card>
    </div>
    <div class="col-md-5">
        <x-adminlte-card title="{{ __('general_content.leave_new_type_trans_key') }}" theme="secondary" maximizable>
            <form method="POST" action="{{ route('human.resources.leave.type.store') }}">
                @csrf
                <div class="form-group">
                    <label for="code">{{ __('general_content.code_trans_key') }}</label>
                    <input type="text" class="form-control" name="code" id="code" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label for="label">{{ __('general_content.label_trans_key') }}</label>
                    <input type="text" class="form-control" name="label" id="label" required>
                </div>
                <div class="form-group">
                    <label for="color">{{ __('general_content.color_trans_key') }}</label>
                    <input type="color" class="form-control" name="color" id="color" value="#6c757d">
                </div>
                <div class="form-group">
                    <label for="default_annual_quota">{{ __('general_content.leave_default_quota_trans_key') }}</label>
                    <input type="number" step="0.5" min="0" class="form-control" name="default_annual_quota" id="default_annual_quota" value="0">
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="counts_against_balance" value="0">
                    <input type="checkbox" class="form-check-input" name="counts_against_balance" value="1" id="counts_new" checked>
                    <label class="form-check-label" for="counts_new">{{ __('general_content.leave_counts_against_balance_trans_key') }}</label>
                </div>
                <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </form>
        </x-adminlte-card>
    </div>
</div>
