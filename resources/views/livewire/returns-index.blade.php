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

    <div class="d-flex justify-content-between flex-wrap mb-3">
        <div class="form-group mb-2 mr-2">
            <input type="text" class="form-control" placeholder="{{ __('general_content.search_trans_key') }}" wire:model.debounce.500ms="search">
        </div>
        <div class="form-group mb-2 mr-2">
            <select class="form-control" wire:model="statusFilter">
                <option value="">{{ __('general_content.all_trans_key') }}</option>
                <option value="1">{{ __('returns.status.received') }}</option>
                <option value="2">{{ __('returns.status.diagnosed') }}</option>
                <option value="3">{{ __('returns.status.in_rework') }}</option>
                <option value="4">{{ __('returns.status.closed') }}</option>
            </select>
        </div>
        <div class="mb-2">
            <button class="btn btn-primary" wire:click="toggleCreateForm">
                <i class="fas fa-plus"></i> {{ __('returns.fields.add_return') }}
            </button>
        </div>
    </div>

    @if ($showCreateForm)
        <x-adminlte-card title="{{ __('returns.fields.add_return') }}" theme="info" class="mb-4">
            <form wire:submit.prevent="createReturn">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="return-code">{{ __('returns.fields.code') }}</label>
                        <input type="text" id="return-code" class="form-control" wire:model="code" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="return-label">{{ __('returns.fields.label') }}</label>
                        <input type="text" id="return-label" class="form-control" wire:model="label">
                        @error('label') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="return-delivery">{{ __('returns.fields.delivery') }}</label>
                        <select id="return-delivery" class="form-control" wire:model="deliverys_id">
                            <option value="">{{ __('returns.fields.choose') }}</option>
                            @foreach ($deliveries as $delivery)
                                <option value="{{ $delivery->id }}">{{ $delivery->code }}</option>
                            @endforeach
                        </select>
                        @error('deliverys_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="return-quality">{{ __('returns.fields.non_conformity') }}</label>
                        <select id="return-quality" class="form-control" wire:model="quality_non_conformity_id">
                            <option value="">{{ __('returns.fields.choose') }}</option>
                            @foreach ($nonConformities as $nc)
                                <option value="{{ $nc->id }}">{{ $nc->code }}</option>
                            @endforeach
                        </select>
                        @error('quality_non_conformity_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-8">
                        <label for="return-report">{{ __('returns.fields.customer_report') }}</label>
                        <textarea id="return-report" class="form-control" rows="3" wire:model="customer_report"></textarea>
                        @error('customer_report') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <h5>{{ __('returns.fields.lines') }}</h5>
                    @error('lines') <span class="text-danger d-block">{{ $message }}</span> @enderror
                    @foreach ($lines as $index => $line)
                        <div class="border rounded p-3 mb-3">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>{{ __('returns.fields.delivery_line') }}</label>
                                    <select class="form-control" wire:model="lines.{{ $index }}.delivery_line_id">
                                        <option value="">{{ __('returns.fields.choose') }}</option>
                                        @foreach ($deliveryLineChoices as $choice)
                                            <option value="{{ $choice['id'] }}">
                                                {{ $choice['ordre'] ?? '' }} - {{ $choice['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("lines.$index.delivery_line_id") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label>{{ __('returns.fields.task') }}</label>
                                    <input type="number" class="form-control" wire:model="lines.{{ $index }}.original_task_id">
                                    @error("lines.$index.original_task_id") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label>{{ __('general_content.qty_trans_key') }}</label>
                                    <input type="number" min="1" class="form-control" wire:model="lines.{{ $index }}.qty">
                                    @error("lines.$index.qty") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger" wire:click="removeLine({{ $index }})" @if(count($lines) === 1) disabled @endif>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>{{ __('returns.fields.issue') }}</label>
                                    <textarea class="form-control" rows="2" wire:model="lines.{{ $index }}.issue_description"></textarea>
                                    @error("lines.$index.issue_description") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('returns.fields.action') }}</label>
                                    <textarea class="form-control" rows="2" wire:model="lines.{{ $index }}.rework_instructions"></textarea>
                                    @error("lines.$index.rework_instructions") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-outline-primary" wire:click="addLine">
                        <i class="fas fa-plus"></i> {{ __('returns.fields.add_line') }}
                    </button>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> {{ __('returns.fields.save') }}
                    </button>
                </div>
            </form>
        </x-adminlte-card>
    @endif

    <x-adminlte-card title="{{ __('returns.fields.list') }}" theme="primary">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th wire:click="sortBy('code')" style="cursor:pointer">{{ __('returns.fields.code') }}</th>
                        <th wire:click="sortBy('label')" style="cursor:pointer">{{ __('returns.fields.label') }}</th>
                        <th>{{ __('returns.fields.delivery') }}</th>
                        <th>{{ __('returns.fields.non_conformity') }}</th>
                        <th>{{ __('returns.fields.status') }}</th>
                        <th wire:click="sortBy('created_at')" style="cursor:pointer">{{ __('returns.fields.created_at') }}</th>
                        <th>{{ __('returns.fields.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td>{{ $return->code }}</td>
                            <td>{{ $return->label }}</td>
                            <td>{{ optional($return->delivery)->code }}</td>
                            <td>{{ optional($return->qualityNonConformity)->code }}</td>
                            <td>
                                <span class="badge badge-info">{{ $return->status_label }}</span>
                            </td>
                            <td>{{ $return->created_at?->format('d/m/Y') }}</td>
                            <td class="d-flex flex-wrap">
                                <button class="btn btn-sm btn-outline-info mr-2 mb-1" wire:click="startDiagnosis({{ $return->id }})">
                                    <i class="fas fa-stethoscope"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary mr-2 mb-1" wire:click="reopenTasks({{ $return->id }})" @if($return->statu >= 4) disabled @endif>
                                    <i class="fas fa-undo"></i> {{ __('returns.fields.reopen_tasks') }}
                                </button>
                                <button class="btn btn-sm btn-outline-success mr-2 mb-1" wire:click="prepareClosure({{ $return->id }})" @if($return->statu === 4) disabled @endif>
                                    <i class="fas fa-check"></i> {{ __('returns.fields.close_return') }}
                                </button>
                                <a href="{{ route('returns.show', ['id' => $return->id]) }}" class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="fas fa-eye"></i> {{ __('returns.fields.view') }}
                                </a>
                            </td>
                        </tr>
                        @if ($selectedReturnId === $return->id)
                            <tr>
                                <td colspan="7">
                                    <form wire:submit.prevent="submitDiagnosis">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>{{ __('returns.fields.diagnosis') }}</label>
                                                <textarea class="form-control" rows="3" wire:model="diagnosisNotes"></textarea>
                                                @error('diagnosisNotes') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>{{ __('returns.fields.customer_report') }}</label>
                                                <textarea class="form-control" rows="3" wire:model="diagnosisCustomerReport"></textarea>
                                                @error('diagnosisCustomerReport') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> {{ __('returns.fields.save') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                        @if ($closingReturnId === $return->id)
                            <tr>
                                <td colspan="7">
                                    <form wire:submit.prevent="closeReturn">
                                        <div class="form-group">
                                            <label>{{ __('returns.fields.closure_comment') }}</label>
                                            <textarea class="form-control" rows="3" wire:model="closureNotes"></textarea>
                                            @error('closureNotes') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> {{ __('returns.fields.close_return') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $returns->links() }}
        </div>
    </x-adminlte-card>
</div>
