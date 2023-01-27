        @if(session('success'))
        <x-adminlte-alert theme="success" title="Success">
            {{ session('success')}}
        </x-adminlte-alert>
        @endif
        @if($errors->count())
        <x-adminlte-alert theme="warning" title="Warning">
            <ul>
                @foreach ( $errors->all() as $message)
                <li> {{ $message }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
        @endif

        @if (session()->has('error'))
            <x-adminlte-alert theme="warning" title="Warning">
                {{ session('error') }}
            
        </x-adminlte-alert>
        @endif