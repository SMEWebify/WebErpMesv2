@extends('auth.n2p-layout')

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@stop

@php( $login_url = View::getSection('login_url') ?? config('adminlte.login_url', '/login/store') )
@php( $register_url = View::getSection('register_url') ?? config('adminlte.register_url', 'register') )
@php( $password_reset_url = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset') )

@if (config('adminlte.use_route_url', false))
    @php( $login_url = $login_url ? route($login_url) : '' )
    @php( $register_url = $register_url ? route($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? route($password_reset_url) : '' )
@else
    @php( $login_url = $login_url ? url($login_url) : '' )
    @php( $register_url = $register_url ? url($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? url($password_reset_url) : '' )
@endif

@section('auth_card_eyebrow', __('adminlte::adminlte.sign_in'))
@section('auth_header', 'Bon retour')
@section('auth_card_sub', __('adminlte::adminlte.login_message'))

@section('auth_body')
    <form action="{{ route('login.store') }}" method="post">
        @csrf
        @if (session('message'))
            <div class="alert alert-danger">{{ session('message') }}</div>
        @endif

        {{-- Champ de connexion selon le driver --}}
        <div class="input-group mb-3">
            @if(env('AUTH_DRIVER') === 'ldap')
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                    value="{{ old('username') }}" placeholder="{{ __('adminlte::adminlte.username') }}" autofocus>

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                @error('username')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror

            @else
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" placeholder="{{ __('adminlte::adminlte.email') }}" autofocus>

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            @endif
        </div>

        {{-- Password field --}}
        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                placeholder="{{ __('adminlte::adminlte.password') }}">

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Mode field --}}
        @if(config('auth.login_mode_selector_enabled'))
            <div class="input-group mb-3">
                <select name="modeView" id="modeView" class="form-control">
                    <option value="desktop">Desktop</option>
                    <option value="workshop">Workshop (Beta)</option>
                </select>

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fa fa-window-maximize {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>
            </div>
        @else
            <input type="hidden" name="modeView" value="desktop">
        @endif

        {{-- Remember me + forgot --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="icheck-primary" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">
                    {{ __('adminlte::adminlte.remember_me') }}
                </label>
            </div>

            @if($password_reset_url)
                <a href="{{ $password_reset_url }}" style="font-size:13px; color: var(--n2p-blue); text-decoration:none;">
                    {{ __('adminlte::adminlte.i_forgot_my_password') }}
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}">
            <span class="fas fa-sign-in-alt"></span>
            {{ __('adminlte::adminlte.sign_in') }}
        </button>
    </form>
@stop

@section('auth_footer')
    @if($register_url)
        <p>
            <a href="{{ $register_url }}">
                {{ __('adminlte::adminlte.register_a_new_membership') }}
            </a>
        </p>
    @else
        <p>
            Pas encore de compte ?
            <a href="https://nest2prod.com/contact" target="_blank" rel="noopener">Contactez Nest2Prod</a>
        </p>
    @endif

    <p class="text-muted">v1.19</p>
@stop
