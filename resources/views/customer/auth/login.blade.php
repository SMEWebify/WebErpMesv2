<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="{{ route('customer.login') }}" class="text-center d-block">
                @if($Factory->picture)
                    <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="{{ $Factory->name }}" class="h-16 w-auto mx-auto">
                @else
                    <span class="text-2xl font-bold text-gray-800">{{ $Factory->name ?? config('app.name', 'WebErpMesv2') }}</span>
                @endif
            </a>
        </x-slot>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('customer.login.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-label for="email" value="{{ __('adminlte::adminlte.email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-label for="password" value="{{ __('adminlte::adminlte.password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label for="remember" class="inline-flex items-center">
                    <input id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">{{ __('adminlte::adminlte.remember_me') }}</span>
                </label>
            </div>

            <div>
                <x-button class="w-full justify-center">
                    <i class="fas fa-lock-open mr-2"></i>{{ __('adminlte::adminlte.sign_in') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
