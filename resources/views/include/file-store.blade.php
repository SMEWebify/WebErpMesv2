<!-- DOCUMENT STORAGE -->
<x-adminlte-card title="{{ __('general_content.documents_trans_key') }}" theme="info" maximizable>
    <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="far fa-file"></i></span>
            </div>
            <div class="custom-file">
                <input type="hidden" name="{{ $inputName }}" value="{{ $inputValue }}" >
                <input type="file" name="file" class="custom-file-input" id="chooseFile">
                <label class="custom-file-label" for="chooseFile">{{ __('general_content.choose_file_trans_key') }}</label>
            </div>
            <div class="input-group-append">
                <button type="submit" name="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
            </div>
        </div>
    </form>

    <h5 class="mt-5 text-muted">{{ __('general_content.attached_file_trans_key') }} </h5>
    <ul class="list-unstyled">
        @forelse ( $filesList as $file)
        <li>
            <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->GetPrettySize() }}</small></a>
        </li>
        @empty
            {{ __('general_content.no_data_trans_key') }}
        @endforelse
    </ul>
</x-adminlte-card>