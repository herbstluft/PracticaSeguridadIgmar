<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
| Rutas de Autenticación (Invitados / Sin Iniciar Sesión)
*/
Route::middleware('guest')->group(function () {
    // Registro de Usuarios
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('refresh-register-captcha', [RegisteredUserController::class, 'refreshCaptcha'])
                ->name('captcha.register.refresh');

    // Inicio de Sesión (Paso 1: Credenciales)
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('refresh-captcha', [AuthenticatedSessionController::class, 'refreshCaptcha'])
                ->name('captcha.refresh');

    // Verificación OTP (Paso 2: Código de un solo uso por Correo)
    Route::get('verify-otp', [AuthenticatedSessionController::class, 'showOtpForm'])
                ->name('verify-otp');
    Route::post('verify-otp', [AuthenticatedSessionController::class, 'verifyOtp'])
                ->name('verify-otp.store');
    Route::post('resend-otp', [AuthenticatedSessionController::class, 'resendOtp'])
                ->name('resend-otp');

    // Verificación 2FA (Paso 3: Google Authenticator o Códigos de Respaldo)
    Route::get('verify-2fa', [AuthenticatedSessionController::class, 'show2faForm'])
                ->name('verify-2fa');
    Route::post('verify-2fa', [AuthenticatedSessionController::class, 'verify2fa'])
                ->name('verify-2fa.store');

    // Restablecimiento de Contraseñas
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

/*
| Rutas de Autenticación (Usuarios Autenticados)
*/
Route::middleware('auth')->group(function () {
    // Verificación de Correo Electrónico
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    // Confirmación y Actualización de Contraseña
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Cierre de Sesión
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
