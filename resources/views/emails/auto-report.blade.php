<!DOCTYPE html>
<html>
<head>
    <title>{{ $reportTitle }}</title>
</head>
<body>
    <h2>{{ $reportTitle }}</h2>

    <p>{{ __('general_content.automatic_email_reports_intro_trans_key', ['name' => $user->name]) }}</p>

    <p><strong>{{ __('general_content.generated_at_trans_key') }} :</strong> {{ $reportData['generated_at'] }}</p>

    @if (empty($reportData['rows']))
        <p>{{ __('general_content.no_data_trans_key') }}</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    @foreach ($reportData['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData['rows'] as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
