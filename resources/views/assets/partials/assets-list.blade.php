<div>
    <h1>{{ __('general_content.assets_trans_key') }}</h1>
    <a href="{{ route('assets.create') }}">Create Asset</a>
    <ul>
        @foreach($assets as $asset)
            <li><a href="{{ route('assets.show', $asset->id) }}">{{ $asset->name }}</a></li>
        @endforeach
    </ul>
    {{ $assets->links() }}
</div>
