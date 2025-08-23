<!DOCTYPE html>
<html>
<head>
    <title>Asset Details</title>
</head>
<body>
    <h1>{{ $asset->name }}</h1>
    <p>Category: {{ $asset->category }}</p>
    <p>Acquisition Value: {{ $asset->acquisition_value }}</p>
    <p>Acquisition Date: {{ $asset->acquisition_date->format('Y-m-d') }}</p>
    <p>Depreciation Duration: {{ $asset->depreciation_duration }} months</p>
    <a href="{{ route('assets.edit', $asset->id) }}">Edit</a>
    <form method="POST" action="{{ route('assets.destroy', $asset->id) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
    <h2>Accounting Entries</h2>
    <ul>
        @foreach($asset->accountingEntries as $entry)
            <li>{{ $entry->entry_label }} - {{ $entry->debit_amount }} / {{ $entry->credit_amount }}</li>
        @endforeach
    </ul>
</body>
</html>
