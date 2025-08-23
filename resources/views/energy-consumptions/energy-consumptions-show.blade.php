<!DOCTYPE html>
<html>
<head>
    <title>Energy Consumption</title>
</head>
<body>
    <h1>Energy Consumption Detail</h1>
    <p>Date: {{ $energyConsumption->recorded_at }}</p>
    <p>Amount: {{ $energyConsumption->amount }} kWh</p>
    <a href="{{ route('energy-consumptions.index') }}">Back to list</a>
</body>
</html>
