<div class="table-responsive">
    <form wire:submit.prevent="filterLogs">
        
        @if(!$subjectType)
        <div class="form-group col-md-3">
            <label for="model">Subject Type</label>
            <select class="form-control" id="model" wire:model.defer="model" placeholder="Subject Type">
                <option value="">Subject Type</option>
                @foreach ($availableModels as $availableModel)
                    <option value="{{ $availableModel }}">{{ $availableModel }}</option>
                @endforeach
            </select>
            @error('model') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        @endif
        <div class="form-group col-md-3">
            <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
            <select class="form-control" id="user_id" wire:model.defer="userId">
                <option value="">{{ __('general_content.view_all_trans_key') }}</option>
                @foreach ($availableUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            @error('userId') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-3">
            <label for="date">{{__('general_content.start_date_trans_key') }}</label>
            <input type="date" class="form-control" id="start_date" wire:model.defer="startDate" placeholder="{{__('general_content.start_date_trans_key') }}">
            @error('startDate') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-3">
            <label for="date">{{__('general_content.end_date_trans_key') }}</label>
            <input type="date" class="form-control" id="end_date" wire:model.defer="endDate" placeholder="{{__('general_content.end_date_trans_key') }}">
            @error('endDate') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-3">
            <x-adminlte-button class="btn mt-4" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
        </div>
    </form>


    @isset($logs)
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">{{__('general_content.label_trans_key') }}</th>
                    <th scope="col">Subject Type</th>
                    <th scope="col">Causer Type</th>
                    <th scope="col">Properties</th>
                    <th scope="col">{{__('general_content.created_trans_key') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <th>{{ $log->id }}</th>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->subject_type }}</td>
                        <td>{{ $log->causer_type }}</td>
                        <td>
                            @if (!empty($log->properties['old']) || !empty($log->properties['attributes']))
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Old Value</th>
                                            <th>New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($log->properties['attributes'] as $key => $newValue)
                                        
                                            <tr>
                                                <td>{{ ucfirst($key) }}</td>
                                                <td>{{ $log->properties['old'][$key] ?? 'N/A' }}</td>
                                                <td>
                                                    @if (isset($log->properties['old'][$key]) && $log->properties['old'][$key] != $newValue)
                                                        <!-- Only show badge if old and new values are different -->
                                                        <span class="badge badge-warning">{{ $newValue }}</span>
                                                    @else
                                                        <!-- Display the new value without the badge if it's the same as old -->
                                                        {{ $newValue ?? 'N/A' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </td>
                        <td>{{ $log->created_at }}</td>
                    </tr>
                @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                @endforelse
            </tbody>
        </table>
    @endisset
</div>