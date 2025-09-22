<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <x-adminlte-card title="{{ $return->code }}" theme="primary" icon="fas fa-undo">
        <dl class="row mb-0">
            <dt class="col-sm-3">{{ __('returns.fields.label') }}</dt>
            <dd class="col-sm-9">{{ $return->label }}</dd>

            <dt class="col-sm-3">{{ __('returns.fields.delivery') }}</dt>
            <dd class="col-sm-9">
                @if($return->delivery)
                    <a href="{{ route('deliverys.show', ['id' => $return->delivery->id]) }}" class="btn btn-outline-secondary btn-sm">{{ $return->delivery->code }}</a>
                @else
                    <span class="text-muted">{{ __('general_content.no_data_trans_key') }}</span>
                @endif
            </dd>

            <dt class="col-sm-3">{{ __('returns.fields.non_conformity') }}</dt>
            <dd class="col-sm-9">
                @if($return->qualityNonConformity)
                    <a href="{{ route('quality.nonConformitie') }}" class="btn btn-outline-secondary btn-sm">{{ $return->qualityNonConformity->code }}</a>
                @else
                    <span class="text-muted">{{ __('general_content.no_data_trans_key') }}</span>
                @endif
            </dd>

            <dt class="col-sm-3">{{ __('returns.fields.status') }}</dt>
            <dd class="col-sm-9"><span class="badge badge-info">{{ $return->status_label }}</span></dd>

            <dt class="col-sm-3">{{ __('returns.fields.created_at') }}</dt>
            <dd class="col-sm-9">{{ $return->created_at?->format('d/m/Y H:i') }}</dd>
        </dl>
    </x-adminlte-card>

    <x-adminlte-card title="{{ __('returns.fields.lines') }}" theme="info">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('returns.fields.delivery_line') }}</th>
                        <th>{{ __('returns.fields.task') }}</th>
                        <th>{{ __('returns.fields.issue') }}</th>
                        <th>{{ __('returns.fields.action') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('returns.fields.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($return->lines as $line)
                        <tr>
                            <td>{{ $line->deliveryLine->label ?? '-' }}</td>
                            <td>
                                {{ $line->original_task_id ?? '-' }}
                                @if($line->rework_task_id)
                                    <span class="badge badge-success ml-2">{{ $line->rework_task_id }}</span>
                                @endif
                            </td>
                            <td>{{ $line->issue_description }}</td>
                            <td>{{ $line->rework_instructions }}</td>
                            <td>{{ $line->qty }}</td>
                            <td>
                                @if($line->rework_task_id)
                                    <span class="badge badge-success">{{ __('returns.status.in_rework') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('returns.status.received') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-adminlte-card>

    <div class="row">
        <div class="col-md-6">
            <x-adminlte-card title="{{ __('returns.fields.diagnosis') }}" theme="warning">
                <form wire:submit.prevent="submitDiagnosis">
                    <div class="form-group">
                        <label>{{ __('returns.fields.diagnosis') }}</label>
                        <textarea class="form-control" rows="4" wire:model="diagnosisNotes"></textarea>
                        @error('diagnosisNotes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('returns.fields.customer_report') }}</label>
                        <textarea class="form-control" rows="3" wire:model="diagnosisCustomerReport"></textarea>
                        @error('diagnosisCustomerReport') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> {{ __('returns.fields.save') }}
                    </button>
                </form>
            </x-adminlte-card>
        </div>
        <div class="col-md-6">
            <x-adminlte-card title="{{ __('returns.fields.actions') }}" theme="secondary">
                <div class="mb-3">
                    <button class="btn btn-outline-secondary" wire:click="reopenTasks()" @if($return->statu >= 4) disabled @endif>
                        <i class="fas fa-undo"></i> {{ __('returns.fields.reopen_tasks') }}
                    </button>
                </div>
                <form wire:submit.prevent="closeReturn">
                    <div class="form-group">
                        <label>{{ __('returns.fields.closure_comment') }}</label>
                        <textarea class="form-control" rows="3" wire:model="closureNotes"></textarea>
                        @error('closureNotes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-success" @if($return->statu === 4) disabled @endif>
                        <i class="fas fa-check"></i> {{ __('returns.fields.close_return') }}
                    </button>
                </form>
            </x-adminlte-card>
        </div>
    </div>
</div>
