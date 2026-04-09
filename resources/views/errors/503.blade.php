<!doctype html>
<title>{{ __('general_content.error_503_title_trans_key') }}</title>
<style>
    body { text-align: center; padding: 150px; }
    h1 { font-size: 50px; }
    body { font: 20px Helvetica, sans-serif; color: #333; }
    article { display: block; text-align: left; width: 650px; margin: 0 auto; }
    a { color: #dc8100; text-decoration: none; }
    a:hover { color: #333; text-decoration: none; }
</style>

<article>
    <img src="/{{ config('branding.logo_img') }}" alt="{{ config('branding.logo_alt') }}">
    <h1>{{ __('general_content.error_503_heading_trans_key') }}</h1>
    <div>
        <p>{{ __('general_content.error_503_message_trans_key') }}</p>
        <p>&mdash; The Team</p>
    </div>
</article>
