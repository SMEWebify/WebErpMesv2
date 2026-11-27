<div class="col-12" data-read-aloud-scope @if($rewriteUrl) data-ai-rewrite-field @endif>
    <label for="comment"  class="text-primary" >
        @if($label)
            {{ $label}}
        @else
            {{ __('general_content.comment_trans_key') }}
        @endif
    </label>
    <div class="input-group">
        <div class="input-group-text">
                <i class="fas fa-lg fa-file-alt text-primary"></i>
            </div>
        <textarea class="form-control" rows="5" name="{{  $name }}"  placeholder="..." >{{  $comment }}</textarea>
    </div>
    @if($rewriteUrl)
    <div class="mt-1" data-ai-rewrite data-url="{{ $rewriteUrl }}" data-max="{{ $maxLength }}" data-trans="{{ json_encode(__('ai_text')) }}">
        <div class="d-flex flex-wrap gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-ai-rewrite-button>
                <i class="fas fa-spell-check me-1"></i>{{ __('ai_text.rewrite') }}
            </button>
            @include('include.read-aloud-button')
        </div>
        <div data-ai-rewrite-result></div>
    </div>
    @elseif(auth()->user()?->ui_readable)
    {{-- Lecture facilitée sans IA : seulement « Lire à voix haute ». --}}
    <div class="mt-1">@include('include.read-aloud-button')</div>
    @endif
</div>