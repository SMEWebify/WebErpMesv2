@extends('adminlte::page')

@section('title', __('general_content.email_trans_key'))

@section('content_header')
<h1>{{ __('general_content.email_trans_key') }}</h1>
@stop

@section('content')

    @if(session('pdfErrorTitle'))
        <x-adminlte-alert theme="danger" dismissable>
            <strong>{{ session('pdfErrorTitle') }}</strong>
            @if(session('pdfErrorItems'))
                <ul class="mb-0 mt-2">
                    @foreach(session('pdfErrorItems') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </x-adminlte-alert>
    @endif

    @if($errors->any())
        <x-adminlte-alert theme="danger" dismissable>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <form action="{{ route('email.send', ['type' => $type, 'id' => $model->id]) }}" method="POST" enctype="multipart/form-data">
        <x-adminlte-card title="{{ __('general_content.new_mail_trans_key') }}" theme="primary" maximizable>
            @csrf
            <div class="form-group">
                <input type="email" name="to" class="form-control" placeholder="" value="{{ old('to', $contactMail) }}" required>
            </div>
            <div class="form-group">
                <input type="text" name="subject" class="form-control" placeholder="{{ __('general_content.object_trans_key') }} :" value="{{ old('subject', $object) }}" required>
            </div>
            <div class="form-group">
                @php
                $config = [
                    "height" => "200",
                    "toolbar" => [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']],
                    ],
                ]
                @endphp
                <x-adminlte-text-editor name="message" label="{{ __('general_content.message_trans_key') }}" label-class="text-primary"
                    igroup-size="sm" placeholder="..." :config="$config"> {!! old('message', $emailTemplate->content ?? '') !!}
                </x-adminlte-text-editor>
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="attach_pdf" value="0">
                    <input type="checkbox" id="attach_pdf" name="attach_pdf" value="1"
                           class="custom-control-input"
                           @checked(old('attach_pdf', true))>
                    <label class="custom-control-label" for="attach_pdf">
                        <i class="fas fa-file-pdf text-danger"></i>
                        {{ __('general_content.attach_document_pdf_trans_key') }}
                        <small class="text-muted">({{ $model->code }}.pdf)</small>
                    </label>
                </div>
                @if($pdfPreviewUrl)
                    <a href="{{ $pdfPreviewUrl }}" target="_blank" class="btn btn-link btn-sm p-0 mt-1">
                        <i class="fas fa-external-link-alt"></i> {{ __('general_content.open_in_new_tab_trans_key') }}
                    </a>
                @endif
            </div>

            <div class="form-group">
                <label for="attachment">{{ __('general_content.attachment_trans_key') }}
                    <small class="text-muted">({{ __('general_content.optional_trans_key') }})</small>
                </label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-file"></i></span>
                    </div>
                    <div class="custom-file">
                        <input type="file" name="attachment" class="custom-file-input" id="chooseFile">
                        <label class="custom-file-label" for="chooseFile">{{ __('general_content.choose_file_trans_key') }}</label>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ url()->previous() }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('general_content.back_trans_key') }}</a>
                <button type="submit" class="btn btn-primary m-4"><i class="fas fa-envelope"></i> {{ __('general_content.to_submit_trans_key') }}</button>
            </div>
        </x-adminlte-card>
    </form>

    @if($pdfPreviewUrl)
        <x-adminlte-card title="{{ __('general_content.attachment_preview_trans_key') }}" theme="secondary" theme-mode="outline" collapsible>
            <div id="pdf-preview-wrapper" class="border rounded" style="height: 700px;">
                <iframe id="pdf-preview"
                        src="{{ $pdfPreviewUrl }}#toolbar=1&view=FitH"
                        style="width: 100%; height: 100%; border: 0;"
                        title="{{ __('general_content.attachment_preview_trans_key') }}"></iframe>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle"></i>
                {{ __('general_content.attachment_preview_hint_trans_key') }}
            </small>
        </x-adminlte-card>
    @endif

    @if($model->emailLogs->isNotEmpty())
        <x-adminlte-card title="{{ __('general_content.email_history_trans_key') }}" theme="secondary" collapsible>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ __('general_content.date_trans_key') }}</th>
                        <th>{{ __('general_content.to_trans_key') }}</th>
                        <th>{{ __('general_content.object_trans_key') }}</th>
                        <th>{{ __('general_content.by_trans_key') }}</th>
                        <th>{{ __('general_content.status_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->emailLogs()->latest()->get() as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->to }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($log->subject, 60) }}</td>
                            <td>{{ $log->sender->name ?? '—' }}</td>
                            <td>
                                @switch($log->status)
                                    @case('sent')
                                        <span class="badge badge-success" title="{{ $log->sent_at?->format('d/m/Y H:i') }}">
                                            <i class="fas fa-check"></i> {{ __('general_content.email_status_sent_trans_key') }}
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-danger" title="{{ $log->error }}">
                                            <i class="fas fa-times"></i> {{ __('general_content.email_status_failed_trans_key') }}
                                        </span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-clock"></i> {{ __('general_content.email_status_pending_trans_key') }}
                                        </span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-adminlte-card>
    @endif

@stop

@section('js')
<script>
    // Affiche le nom du fichier choisi dans le label (custom-file AdminLTE).
    document.getElementById('chooseFile')?.addEventListener('change', function (e) {
        const label = this.nextElementSibling;
        if (label && e.target.files.length) {
            label.textContent = e.target.files[0].name;
        }
    });
</script>
@stop
