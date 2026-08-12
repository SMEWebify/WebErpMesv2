<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;


Route::middleware('guest')->group(function () {

    // Route to display the login form
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Equivalent strict de redirect()->route('login') : ce nom resout vers /login
    // non localise (auth.php est enregistre une seconde fois hors groupe de locale
    // par RouteServiceProvider, et le dernier enregistrement gagne sur le nom).
    // Ecrit en Route::redirect car une closure n'est pas serialisable et bloquerait
    // route:trans:cache pour les 760 routes.
    Route::redirect('/', '/login');

    // Route to handle login form submission
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->name('login.store');

    // Self-registration (disabled by default — set REGISTRATION_ENABLED=true in .env to enable)
    if (config('branding.registration_enabled')) {
        Route::get('/register', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('/register', [RegisteredUserController::class, 'store']);
    }

    // Route to display the forgot password form
    Route::get('/forgot/password', [PasswordResetLinkController::class, 'create'])
        ->name('forgot.password');

    // Route to handle forgot password form submission and send reset email
    Route::post('/forgot/password', [PasswordResetLinkController::class, 'store'])
        ->name('forgot.password.email');

    // Route to display the password reset form with token
    Route::get('/password/reset/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    // Route to handle password reset form submission and save new password
    Route::post('/password/reset/store', [NewPasswordController::class, 'store'])
        ->name('password.store');

//    Route::post('/password/reset/update', [PasswordController::class, 'update'])
//        ->middleware('guest')
//        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
