<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('general_content.attachment_preview_trans_key') }}</title>
    <style>
        /* Page autonome — l'iframe n'hérite pas des styles AdminLTE.
           On garde un look sobre, lisible sans dépendance externe. */
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            background: #f8f9fa;
            height: 100%;
        }
        .wrap {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: #fff;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
            padding: 24px 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .card h1 {
            color: #721c24;
            font-size: 17px;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card h1 svg { flex: 0 0 22px; }
        .card p.title {
            font-weight: 600;
            font-size: 15px;
            color: #212529;
            margin: 12px 0 8px;
            line-height: 1.4;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin: 6px 0;
            line-height: 1.45;
            font-size: 14px;
        }
        .hint {
            color: #6c757d;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="#dc3545">
                    <path d="M12 2 1 21h22L12 2zm1 15h-2v-2h2v2zm0-4h-2V9h2v4z"/>
                </svg>
                {{ __('general_content.email_pdf_preview_error_trans_key') }}
            </h1>

            <p class="title">{{ $title }}</p>

            @if(! empty($items))
                <ul>
                    @foreach($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            <p class="hint">
                {{ __('general_content.email_pdf_preview_error_hint_trans_key') }}
            </p>
        </div>
    </div>
</body>
</html>
