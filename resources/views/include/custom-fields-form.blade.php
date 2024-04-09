    <div class="col-md-6">
        <form method="POST" action="{{ route('admin.factory.custom.field.value.store.update', ['id' => $id,'type' => $type, ]) }}">
        @csrf
        <div class="card-body">
            @foreach ($CustomFields as $customField)
            <div class="form-group">
            <label  for="{{ $customField->name }}<">{{ $customField->name }}</label>
                <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                @if ($customField->field_value)
                    @if ($customField->type === 'checkbox')
                    <div class="form-check">
                        <input class="form-control" type="checkbox" id="{{ $customField->name }}" name="custom_fields[{{ $customField->id }}]" {{ $customField->field_value ? 'checked' : '' }}>
                    </div>
                    @else
                    <input class="form-control" type="{{ $customField->type }}" name="custom_fields[{{ $customField->id }}]" value="{{ $customField->field_value }}">
                    @endif
                @else
                    @if ($customField->type === 'checkbox')
                    <div class="form-check">
                        <input class="form-control" type="checkbox" id="{{ $customField->name }}" name="custom_fields[{{ $customField->id }}]">
                    </div>
                    @else
                    <input class="form-control" type="{{ $customField->type }}" id="{{ $customField->name }}" name="custom_fields[{{ $customField->id }}]" placeholder="Saisissez une valeur...">
                    @endif 
                @endif
                </div>
            </div>
            @endforeach
        </div>
        <div class="card-footer">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
        </div>
        </form>
    </div>