    <div class="col-md-6">
        <form method="POST" action="{{ route('admin.factory.custom.field.value.store.update', ['id' => $id,'type' => $type, ]) }}">
            @csrf
            <div class="card-body">
                @php
                    $defaultCategoryLabel = __('general_content.custom_fields_default_category_trans_key');
                    $groupedCustomFields = $CustomFields->groupBy(function ($field) use ($defaultCategoryLabel) {
                        return $field->category ?? $defaultCategoryLabel;
                    });
                @endphp

                @forelse ($groupedCustomFields as $categoryLabel => $fields)
                    <h5 class="text-muted mb-3">{{ $categoryLabel }}</h5>

                    @foreach ($fields as $customField)
                        @php
                            $fieldInputId = 'custom-field-' . $customField->id;
                            $fieldValue = old("custom_fields.{$customField->id}", $customField->field_value);
                        @endphp

                        <div class="form-group">
                            @if ($customField->type === 'checkbox')
                                <input type="hidden" name="custom_fields[{{ $customField->id }}]" value="0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="{{ $fieldInputId }}" name="custom_fields[{{ $customField->id }}]" value="1" {{ $fieldValue ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $fieldInputId }}">{{ $customField->name }}</label>
                                </div>
                            @elseif ($customField->type === 'select')
                                <label for="{{ $fieldInputId }}">{{ $customField->name }}</label>
                                @php
                                    $fieldOptions = is_array($customField->options) ? $customField->options : [];
                                @endphp
                                <select class="form-control" id="{{ $fieldInputId }}" name="custom_fields[{{ $customField->id }}]">
                                    <option value="">{{ __('general_content.custom_fields_select_placeholder_trans_key') }}</option>
                                    @foreach ($fieldOptions as $option)
                                        <option value="{{ $option }}" {{ $fieldValue === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @else
                                <label for="{{ $fieldInputId }}">{{ $customField->name }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                    </div>
                                    <input class="form-control" type="{{ $customField->type }}" id="{{ $fieldInputId }}" name="custom_fields[{{ $customField->id }}]" value="{{ $fieldValue }}" placeholder="{{ __('general_content.custom_fields_placeholder_trans_key') }}">
                                </div>
                            @endif
                        </div>
                    @endforeach
                @empty
                    <p class="text-muted">{{ __('general_content.no_data_trans_key') }}</p>
                @endforelse
            </div>
            <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
        </form>
    </div>