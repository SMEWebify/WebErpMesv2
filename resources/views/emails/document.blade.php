<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->code ?? '' }}</title>
</head>
<body>
    {{-- Ni titre ni id interne : le message, signature comprise, est ce que l'utilisateur a relu. --}}
    <div>{!! $messageContent !!}</div>
</body>
</html>
