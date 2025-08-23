<!DOCTYPE html>
<html>
<head>
    <title>{{ __('general_content.energy_consumption_trans_key') }}</title>
</head>
<body>
    <h1>{{ __('general_content.energy_consumption_trans_key') }}</h1>
    <form method="POST" action="{{ route('energy-consumptions.store') }}">
        @csrf
        <div>
            <label for="recorded_at">Date</label>
            <input type="date" name="recorded_at" id="recorded_at" required>
        </div>
        <div>
            <label for="amount">Amount (kWh)</label>
            <input type="number" step="0.01" name="amount" id="amount" required>
        </div>
        <button type="submit">Save</button>
    </form>
    <ul>
        @foreach ($energyConsumptions as $energyConsumption)
            <li>
                <a href="{{ route('energy-consumptions.show', $energyConsumption->id) }}">
                    {{ $energyConsumption->recorded_at }} - {{ $energyConsumption->amount }} kWh
                </a>
            </li>
        @endforeach
    </ul>
</body>
</html>
