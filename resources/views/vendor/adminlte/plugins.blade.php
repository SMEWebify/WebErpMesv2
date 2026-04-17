@foreach(config('adminlte.plugins') as $pluginName => $plugin)
    @php
        $plugSection = View::getSection('plugins.' . ($plugin['name'] ?? $pluginName));
        $isPlugActive = $plugin['active']
            ? ! isset($plugSection) || $plugSection
            : ! empty($plugSection);
    @endphp

    @if($isPlugActive)
        @foreach($plugin['files'] as $file)
            @php
                if (! empty($file['asset'])) {
                    $file['location'] = ltrim($file['location'], '/');
                }
            @endphp

            @if($file['type'] == $type && $type == 'css')
                <link rel="stylesheet" href="{{ $file['location'] }}">
            @elseif($file['type'] == $type && $type == 'js')
                <script src="{{ $file['location'] }}" @if(! empty($file['defer'])) defer @endif></script>
            @endif
        @endforeach
    @endif
@endforeach
