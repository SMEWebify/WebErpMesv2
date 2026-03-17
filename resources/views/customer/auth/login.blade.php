<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="{{ route('customer.login') }}" class="text-center d-block text-decoration-none">
                @if($Factory->picture)
                    <img src="data:image/png;base64,{{ $Factory->getImageFactoryPath() }}" alt="{{ $Factory->name }}" class="img-fluid" style="max-height: 4rem;">
                @else
                    <span class="fs-3 fw-bold text-dark">{{ $Factory->name ?? config('app.name', 'WebErpMesv2') }}</span>
                @endif
            </a>
        </x-slot>

        <x-auth-session-status class="mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('customer.login.store') }}">
            @csrf

            <div class="mb-3">
                <x-label for="email" value="{{ __('adminlte::adminlte.email') }}" />
                <x-input id="email" class="mt-1 w-100" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <x-label for="password" value="{{ __('adminlte::adminlte.password') }}" />
                <x-input id="password" class="mt-1 w-100" type="password" name="password" required autocomplete="current-password" />
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <label for="remember" class="d-inline-flex align-items-center gap-2">
                    <input id="remember" type="checkbox" class="form-check-input" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span class="small text-muted">{{ __('adminlte::adminlte.remember_me') }}</span>
                </label>
            </div>

            <div class="d-grid">
                <x-button>
                    <i class="fas fa-lock-open me-2"></i>{{ __('adminlte::adminlte.sign_in') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
