<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('configurators.return_title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 90vh; margin: 0; }
        p { color: #555; }
    </style>
</head>
<body>
    <p>{{ __('configurators.return_text') }}</p>
    <script>
        // Prévient l'écran du devis ou de la commande (même origine que l'ERP), puis
        // referme l'onglet. Le message ne porte que des identifiants : le serveur relit le résultat.
        (function () {
            var message = { type: 'wem-configurator:return', session: @json($session), resultId: @json($resultId) };
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(message, window.location.origin);
                window.close();
            }
        })();
    </script>
</body>
</html>
