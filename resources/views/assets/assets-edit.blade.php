<!DOCTYPE html>
<html>
<head>
    <title>Edit Asset</title>
</head>
<body>
    <h1>Edit Asset</h1>
    <form method="POST" action="{{ route('assets.update', $asset->id) }}">
        @csrf
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $asset->name) }}"><br>
        <label>Category</label>
        <input type="text" name="category" value="{{ old('category', $asset->category) }}"><br>
        <label>Acquisition Value</label>
        <input type="number" step="0.01" name="acquisition_value" value="{{ old('acquisition_value', $asset->acquisition_value) }}"><br>
        <label>Acquisition Date</label>
        <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date->format('Y-m-d')) }}"><br>
        <label>Depreciation Duration (months)</label>
        <input type="number" name="depreciation_duration" value="{{ old('depreciation_duration', $asset->depreciation_duration) }}"><br>
        <button type="submit">Update</button>
    </form>
</body>
</html>
