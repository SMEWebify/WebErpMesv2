@if($modalTarget)
    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalTarget }}">
        <i class="fas  fa-edit"></i>
        {{ __('general_content.edit_trans_key') }}
    </button>
@else
    <a class="btn btn-teal btn-sm" href="{{ $route }}">
        <i class="fas fa-edit"></i>
        {{ __('general_content.edit_trans_key') }}
    </a>
@endif
