<!DOCTYPE html>
<html>
<head>
    <title>{{ __('general_content.energy_consumption_trans_key') }}</title>
</head>
<body>
    <h1>{{ __('general_content.energy_consumption_trans_key') }}</h1>
    <p>Date: {{ $energyConsumption->recorded_at }}</p>
    <p>Amount: {{ $energyConsumption->amount }} kWh</p>
    <a href="{{ route('energy-consumptions.index') }}">Back to list</a>
</body>
</html>
