        @if(session('success'))
        <x-adminlte-alert theme="success" title="{{ __('general_content.alert_success_title_trans_key') }}">
            {{ session('success')}}
        </x-adminlte-alert>
        @endif
        @if(session('warning'))
        <x-adminlte-alert theme="warning" title="{{ __('general_content.alert_warning_title_trans_key') }}">
            {{ session('warning') }}
        </x-adminlte-alert>
        @endif
        @if($errors->count())
        <x-adminlte-alert theme="warning" title="{{ __('general_content.alert_warning_title_trans_key') }}">
            <ul>
                @foreach ( $errors->all() as $message)
                <li> {{ $message }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
        @endif

        @if (session()->has('error'))
            <x-adminlte-alert theme="warning" title="{{ __('general_content.alert_warning_title_trans_key') }}">
                {{ session('error') }}

        </x-adminlte-alert>
        @endif