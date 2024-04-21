<div class="table-responsive">
    <form wire:submit.prevent="filterLogs">
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
                                @if (!empty($log->properties['old']))
                                    <p>Old:</p>
                                    <ul>
                                        @foreach ($log->properties['old'] as $key => $value)
                                            <li>{{ $key }}: {{ $value }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (!empty($log->properties['attributes']))
                                    <p>New:</p>
                                    <ul>
                                        @foreach ($log->properties['attributes'] as $key => $value)
                                            <li>{{ $key }}: {{ $value }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>{{ $log->created_at }}</td>
                        </tr>
                    @empty
                        <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
            </tbody>
        </table>
    @endisset
</div>