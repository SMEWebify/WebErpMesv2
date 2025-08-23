<!DOCTYPE html>
<html>
<head>
    <title>Assets</title>
</head>
<body>
    <h1>Assets</h1>
    <a href="{{ route('assets.create') }}">Create Asset</a>
    <ul>
        @foreach($assets as $asset)
            <li><a href="{{ route('assets.show', $asset->id) }}">{{ $asset->name }}</a></li>
        @endforeach
    </ul>
</body>
</html>
