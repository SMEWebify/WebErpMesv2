<div class="row mb-2">
    <div class="col-sm-6">
        <h1> {{ $h1 }}</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ $previous }}">{{ __('general_content.previous_trans_key') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ $list }}">{{ __('general_content.back_to_list_trans_key') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ $next }}">{{ __('general_content.next_trans_key') }}</a></li>
        </ol>
    </div>
</div>