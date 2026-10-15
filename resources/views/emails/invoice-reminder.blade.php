<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectText }}</title>
</head>
<body>
    {{-- Contenu nettoyé par SafeHtml et variables échappées : InvoiceReminderService. --}}
    {!! $messageContent !!}
</body>
</html>
