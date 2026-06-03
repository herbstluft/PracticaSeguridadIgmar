<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\GoogleAuthenticator;
use App\Mail\OtpMail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Helpers\CaptchaGenerator;
use App\Models\SecurityLog;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar la vista de inicio de sesión.
     */
    public function create(): Response
    {
        $attempts = session('login_attempts', 0);
        $showCaptcha = $attempts >= 3;

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'showCaptcha' => $showCaptcha,
            'captchaSvg' => $showCaptcha ? CaptchaGenerator::generate('login_captcha') : null,
        ]);
    }

    /**
     * Generar un código OTP, guardarlo en el usuario, enviarlo por correo y registrar el evento.
     */
    private function generateAndSendOtp(User $user, Request $request, string $event): void
    {
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otpCode;
        $user->otp_expires_at = now()->addMinutes(1);
        $user->save();

        SecurityLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'email' => $user->email,
            'event' => $event,
            'status' => 'Pendiente',
        ]);

        try {
            Mail::to($user->email)->send(new OtpMail($otpCode));
        } catch (\Exception $e) {
            logger()->error('Error al enviar correo OTP: ' . $e->getMessage());
        }
    }

    /**
     * Refrescar el captcha y retornar el nuevo SVG en JSON.
     */
    public function refreshCaptcha(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'captchaSvg' => CaptchaGenerator::generate('login_captcha'),
        ]);
    }

    /**
     * Manejar el Paso 1 del inicio de sesión: Validación de correo y contraseña.
     */
    public function store(Request $request): RedirectResponse
    {
        $attempts = session('login_attempts', 0);
        $showCaptcha = $attempts >= 3;

        $rules = [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];

        if ($showCaptcha) {
            $rules['captcha'] = 'required|string';
        }

        $request->validate($rules);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // 1. Verificar bloqueo por Rate Limit primero
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            session(['login_attempts' => session('login_attempts', 0) + 1]);

            SecurityLog::create([
                'ip_address' => $request->ip(),
                'email' => $request->email,
                'event' => 'Bloqueo por Rate Limit (Login)',
                'status' => 'Bloqueado (Rate Limit)',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos de inicio de sesión. Por favor, intenta de nuevo en ' . $seconds . ' segundos.',
            ]);
        }

        // 2. Verificar Captcha si corresponde
        if ($showCaptcha) {
            $sessionCaptcha = session('login_captcha');
            session()->forget('login_captcha');

            if (!$sessionCaptcha || strtolower($request->input('captcha')) !== $sessionCaptcha) {
                // Registrar este fallo en el Rate Limiter
                RateLimiter::hit($throttleKey);

                throw ValidationException::withMessages([
                    'captcha' => 'El código de seguridad (Captcha) es incorrecto.',
                ]);
            }
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);

            session(['login_attempts' => session('login_attempts', 0) + 1]);

            SecurityLog::create([
                'user_id' => $user ? $user->id : null,
                'ip_address' => $request->ip(),
                'email' => $request->email,
                'event' => 'Fallo de Contraseña',
                'status' => 'Rechazado',
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (is_null($user->email_verified_at)) {
            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Intento de Acceso (Cuenta No Activa)',
                'status' => 'Rechazado',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Debes activar tu cuenta antes de iniciar sesión. Por favor, revisa tu correo electrónico.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        session()->forget('login_attempts');

        // Si el usuario es de tipo invitado (guest), solo requiere 1 factor (correo y contraseña)
        if ($user->role === 'guest') {
            Auth::login($user, $request->boolean('remember'));

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Autenticación Exitosa (1 Paso - Invitado)',
                'status' => 'Autorizado',
            ]);

            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        // Guardar credenciales temporales en la sesión
        $request->session()->put('auth.temp_user_id', $user->id);
        $request->session()->put('auth.remember', $request->boolean('remember'));

        // Generar y enviar OTP
        $this->generateAndSendOtp($user, $request, 'Código OTP Generado');

        return redirect()->route('verify-otp', ['email' => $user->email]);
    }

    /**
     * Mostrar el Paso 2: Formulario de Verificación OTP.
     */
    public function showOtpForm(Request $request): Response
    {
        $email = $request->query('email');
        $tempUserId = $request->session()->get('auth.temp_user_id');

        if (!$tempUserId) {
            return Inertia::render('Auth/Login', ['status' => 'Sesión expirada. Por favor, inicia sesión de nuevo.']);
        }

        $user = User::find($tempUserId);
        if (!$user || $user->email !== $email) {
            return redirect()->route('login');
        }

        // Pasar los segundos restantes para la expiración
        $expiresIn = 0;
        if ($user->otp_expires_at && now()->isBefore($user->otp_expires_at)) {
            $expiresIn = now()->diffInSeconds($user->otp_expires_at, false);
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => $email,
            'expiresIn' => $expiresIn,
            // Pasamos el código OTP en ambiente local si falla el servidor de correos
            'devOtpCode' => app()->environment('local') ? $user->otp_code : null,
        ]);
    }

    /**
     * Manejar el Paso 2: Validar el código OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string|size:6',
        ]);

        $tempUserId = $request->session()->get('auth.temp_user_id');
        if (!$tempUserId) {
            return redirect()->route('login')->withErrors(['email' => 'La sesión ha expirado.']);
        }

        $user = User::find($tempUserId);
        if (!$user || $user->email !== $request->email) {
            return redirect()->route('login');
        }

        $throttleKey = 'otp|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Bloqueo por Rate Limit (OTP)',
                'status' => 'Bloqueado (Rate Limit)',
            ]);

            throw ValidationException::withMessages([
                'code' => 'Demasiados intentos. Intenta de nuevo en ' . $seconds . ' segundos.',
            ]);
        }

        if ($user->otp_code !== $request->code || now()->isAfter($user->otp_expires_at)) {
            RateLimiter::hit($throttleKey);

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Fallo de Código OTP / Expirado',
                'status' => 'Rechazado',
            ]);

            throw ValidationException::withMessages([
                'code' => 'El código OTP es incorrecto o ha expirado.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Limpiar el código OTP usado
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Si el usuario es de tipo normal (user), solo requiere 2 factores (correo/contraseña -> OTP)
        if ($user->role === 'user') {
            Auth::loginUsingId($user->id, $request->session()->get('auth.remember', false));

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Autenticación Exitosa (2 Pasos - Usuario)',
                'status' => 'Autorizado',
            ]);

            $request->session()->forget(['auth.temp_user_id', 'auth.otp_verified', 'auth.remember']);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        SecurityLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'email' => $user->email,
            'event' => 'Código OTP Validado',
            'status' => 'Exitoso',
        ]);

        $request->session()->put('auth.otp_verified', true);

        return redirect()->route('verify-2fa');
    }

    /**
     * Mostrar el Paso 3: Configuración de Google Authenticator o Formulario de Verificación.
     */
    public function show2faForm(Request $request)
    {
        $tempUserId = $request->session()->get('auth.temp_user_id');
        $otpVerified = $request->session()->get('auth.otp_verified');

        if (!$tempUserId || !$otpVerified) {
            return redirect()->route('login');
        }

        $user = User::find($tempUserId);
        if (!$user) {
            return redirect()->route('login');
        }

        // Configurar 2FA si no ha sido confirmado aún
        if (!$user->two_factor_confirmed_at) {
            $plainBackupCodes = [];

            if (!$user->two_factor_secret) {
                $user->two_factor_secret = GoogleAuthenticator::generateSecret();
                
                // Generar 3 códigos de respaldo de un solo uso
                $hashedCodes = [];
                for ($i = 0; $i < 3; $i++) {
                    $rawCode = strtoupper(Str::random(4) . '-' . Str::random(4));
                    $plainBackupCodes[] = $rawCode;
                    $hashedCodes[] = Hash::make($rawCode);
                }
                
                $user->backup_codes = $hashedCodes;
                $user->save();
                
                // Almacenar temporalmente en sesión para mostrarlos una única vez
                $request->session()->put('auth.backup_codes', $plainBackupCodes);
            } else {
                $plainBackupCodes = $request->session()->get('auth.backup_codes', []);
            }

            $qrCodeUrl = GoogleAuthenticator::getQrCodeUrl(
                config('app.name', 'PracticaSeguridad'),
                $user->email,
                $user->two_factor_secret
            );

            return Inertia::render('Auth/Setup2fa', [
                'secretKey' => $user->two_factor_secret,
                'qrCodeUrl' => $qrCodeUrl,
                'backupCodes' => $plainBackupCodes,
            ]);
        }

        // Verificar 2FA existente
        return Inertia::render('Auth/Verify2fa');
    }

    /**
     * Manejar el Paso 3: Validar código de Google Authenticator.
     */
    public function verify2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $tempUserId = $request->session()->get('auth.temp_user_id');
        $otpVerified = $request->session()->get('auth.otp_verified');

        if (!$tempUserId || !$otpVerified) {
            return redirect()->route('login');
        }

        $user = User::find($tempUserId);
        if (!$user) {
            return redirect()->route('login');
        }

        $throttleKey = '2fa|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            \App\Models\SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Bloqueo por Rate Limit (2FA)',
                'status' => 'Bloqueado (Rate Limit)',
            ]);

            throw ValidationException::withMessages([
                'code' => 'Demasiados intentos. Intenta de nuevo en ' . $seconds . ' segundos.',
            ]);
        }

        $inputCode = strtoupper(trim($request->code));
        $isValid = false;
        $isBackupCode = false;

        // 1. Verificar si tiene formato de código de respaldo (XXXX-XXXX)
        if (preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $inputCode)) {
            $isBackupCode = true;
            $userBackupCodes = $user->backup_codes ?: [];

            foreach ($userBackupCodes as $index => $hashedCode) {
                if (Hash::check($inputCode, $hashedCode)) {
                    $isValid = true;
                    // Eliminar código de respaldo usado
                    unset($userBackupCodes[$index]);
                    $user->backup_codes = array_values($userBackupCodes);
                    $user->save();
                    break;
                }
            }
        } else {
            // 2. Validar código TOTP estándar
            $isValid = GoogleAuthenticator::verifyCode($user->two_factor_secret, $inputCode);
        }

        if (!$isValid) {
            RateLimiter::hit($throttleKey);

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => $isBackupCode ? 'Intento fallido con Código de Respaldo' : 'Intento fallido con Google Authenticator',
                'status' => 'Rechazado',
            ]);

            throw ValidationException::withMessages([
                'code' => $isBackupCode 
                    ? 'El código de respaldo es incorrecto o ya ha sido utilizado.' 
                    : 'El código de Google Authenticator es incorrecto.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Confirmar 2FA si no estaba confirmado previamente
        if (!$user->two_factor_confirmed_at) {
            $user->two_factor_confirmed_at = now();
            $user->save();
        }

        SecurityLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'email' => $user->email,
            'event' => $isBackupCode ? 'Autenticación con Código de Respaldo' : 'Autenticación con Google Authenticator',
            'status' => 'Autorizado',
        ]);

        // Autenticar al usuario finalmente en la sesión
        Auth::loginUsingId($user->id, $request->session()->get('auth.remember', false));

        // Limpiar datos temporales de sesión
        $request->session()->forget(['auth.temp_user_id', 'auth.otp_verified', 'auth.remember', 'auth.backup_codes']);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Reenviar correo electrónico con código OTP.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $tempUserId = $request->session()->get('auth.temp_user_id');
        if (!$tempUserId) {
            return redirect()->route('login')->withErrors(['email' => 'La sesión ha expirado.']);
        }

        $user = User::find($tempUserId);
        if (!$user) {
            return redirect()->route('login');
        }

        $throttleKey = 'resend|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 2)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            SecurityLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'email' => $user->email,
                'event' => 'Bloqueo por Rate Limit (Reenvío OTP)',
                'status' => 'Bloqueado (Rate Limit)',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Por favor, espera ' . $seconds . ' segundos antes de solicitar otro código.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        // Generar y enviar
        $this->generateAndSendOtp($user, $request, 'Código OTP Reenviado');

        return redirect()->route('verify-otp', ['email' => $user->email]);
    }

    /**
     * Destruir la sesión autenticada.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
