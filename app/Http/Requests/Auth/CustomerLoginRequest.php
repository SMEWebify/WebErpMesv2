<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerLoginRequest extends FormRequest
{
    private const LOGIN_ATTEMPTS_LIMIT = 5;
    private const IP_ATTEMPTS_LIMIT = 10;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::guard('customer')->attempt([
            'mail' => $this->input('email'),
            'password' => $this->input('password'),
        ], $this->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (RateLimiter::tooManyAttempts($this->ipThrottleKey(), self::IP_ATTEMPTS_LIMIT)) {
            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => __('Trop de tentatives de connexion depuis cette adresse IP. Réessayez dans :minutes minute(s).', [
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::LOGIN_ATTEMPTS_LIMIT)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return 'customer|'.Str::lower($this->input('email')).'|'.$this->ip();
    }

    public function ipThrottleKey(): string
    {
        return 'customer-ip|'.$this->ip();
    }
}
