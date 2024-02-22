<div class="col-12">
    <label for="comment"  class="text-primary" >
        @if($label)
            {{ $label}}
        @else
            {{ __('general_content.comment_trans_key') }}
        @endif
    </label>
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">
                <i class="fas fa-lg fa-file-alt text-primary"></i>
            </div>
        </div>
        <textarea class="form-control" rows="5" name="{{  $name }}"  placeholder="..." >{{  $comment }}</textarea>
    </div>
</div>