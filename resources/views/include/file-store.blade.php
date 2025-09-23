<!-- DOCUMENT STORAGE -->
<x-adminlte-card title="{{ __('general_content.documents_trans_key') }}" theme="purple" collapsible="collapsed" maximizable>
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

        <div class="form-group mt-3">
            <label for="file-comment-{{ $inputName }}-{{ $inputValue }}">{{ __('general_content.comment_trans_key') }}</label>
            <textarea name="comment" id="file-comment-{{ $inputName }}-{{ $inputValue }}" class="form-control" rows="2" placeholder="{{ __('general_content.comment_trans_key') }}">{{ old('comment') }}</textarea>
        </div>

        <div class="form-group">
            <label for="file-hashtags-{{ $inputName }}-{{ $inputValue }}">{{ __('general_content.hashtags_trans_key') }}</label>
            <input type="text" name="hashtags" id="file-hashtags-{{ $inputName }}-{{ $inputValue }}" class="form-control" value="{{ old('hashtags') }}" placeholder="#tag1 #tag2" />
        </div>
    </form>

    <h5 class="mt-5 text-muted">{{ __('general_content.attached_file_trans_key') }} </h5>
    <ul class="list-unstyled">
        @forelse ( $filesList as $file)
        <li>
            <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->getFormattedSizeAttribute() }}</small></a>
            @if(!empty($file->comment))
                <div class="text-muted small">{!! nl2br(e($file->comment)) !!}</div>
            @endif
            @php($hashtags = $file->hashtags ?? [])
            @if(!empty($hashtags))
                <div class="mt-1">
                    @foreach($hashtags as $hashtag)
                        <span class="badge badge-info">#{{ $hashtag }}</span>
                    @endforeach
                </div>
            @endif
        </li>
        @empty
            {{ __('general_content.no_data_trans_key') }}
        @endforelse
    </ul>
</x-adminlte-card>