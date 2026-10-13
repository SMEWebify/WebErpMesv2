@if($modalTarget)
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalTarget }}">
        <i class="fas fa-folder"></i>
        {{ __('general_content.view_trans_key') }}
    </button>
@elseif($bankFile) 
    <a class="btn btn-primary " href="{{ asset('drawing/' . $bankFile) }}" target="_blank">
        <i class="fa fa-lg fa-fw fa-eye"></i>
    </a>
@else
    <a class="btn btn-primary btn-sm" href="{{ $route }}" 
        @if($downloadFile) download="{{ basename($downloadFile) }}" @endif>
        <i class="fas fa-folder"></i>
        {{ __('general_content.view_trans_key') }}
    </a>
@endif
