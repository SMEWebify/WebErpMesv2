@props(['errors'])
@if ($errors->any())
    <div {{ $attributes }}>
        <div class="alert alert-danger">
            <strong>{{ __('Whoops! Something went wrong.') }}</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
