@if(!empty($customCss))
    <style type="text/css">
{!! \App\Models\Admin\Factory::sanitizePdfCss($customCss) !!}
    </style>
@endif
