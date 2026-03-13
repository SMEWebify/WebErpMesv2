@extends('adminlte::page')

@section('title', __('general_content.template_mail_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.template_mail_trans_key') }}</h1>
@stop

@section('content')

<div class="row">
    <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.template_list_mail_trans_key') }}" theme="primary" maximizable> 
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.entity_type_trans_key') }}</th>
                            <th>{{ __('general_content.object_trans_key') }}</th> 
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emailTemplates as $template)
                            <tr>
                                <td>{{ ucfirst($template->document_type) }}</td>
                                <td>{{ $template->subject }}</td>
                                <td class="py-0 align-middle">
                                    <!-- Button Modal -->
                                    <x-ButtonTextEdit :modalTarget="'Template' . $template->id" />
                                    
                                    <!-- Modal {{ $template->id }} -->
                                    <x-adminlte-modal id="Template{{ $template->id }}" title="Modifier {{ $template->document_type }}" theme="teal" icon="fa fa-pen" size='lg'>
                                        <form method="POST" action="{{ route('admin.emails.templates.update', $template->id) }}">
                                            @csrf
                                            <div class="form-group">
                                                <label>{{ __('general_content.object_trans_key') }} :</label>
                                                <input type="text" name="subject" class="form-control" value="{{ $template->subject }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('general_content.message_trans_key') }} :</label> 
                                                <textarea name="content" class="form-control summernote" required>{{ $template->content }}</textarea>
                                            </div>
                                            <div class="card-footer">
                                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                            </div>
                                        </form>
                                    </x-adminlte-modal>
                                </td>
                            </tr>
                        @empty
                        <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-adminlte-card>
    </div>

    <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.new_template_mail_trans_key') }}" theme="secondary" maximizable> 
            <form method="POST" action="{{ route('admin.emails.templates.store') }}">
                @csrf
                <div class="form-group">
                    <label>{{ __('general_content.entity_type_trans_key') }} :</label>
                    <select class="form-control" name="document_type" required>
                        @forelse($availableDocumentTypes as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @empty
                            <option value="" disabled selected>{{ __('general_content.no_data_trans_key') }}</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('general_content.object_trans_key') }} :</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>{{ __('general_content.message_trans_key') }} :</label>
                    <textarea name="content" class="form-control summernote" required></textarea>
                </div>
                <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save" :disabled="empty($availableDocumentTypes)"/>
                </div>
            </form>
        </x-adminlte-card>
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
<script>
    $(document).ready(function () {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
            ]
        });
    });
</script>
@endsection
