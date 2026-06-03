<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Helpers\CaptchaGenerator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Mostrar la vista de registro.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'captchaSvg' => CaptchaGenerator::generate('register_captcha'),
        ]);
    }

    /**
     * Refrescar el captcha de registro y retornar el nuevo SVG en JSON.
     */
    public function refreshCaptcha(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'captchaSvg' => CaptchaGenerator::generate('register_captcha'),
        ]);
    }

    /**
     * Manejar una solicitud de registro entrante.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $throttleKey = 'register|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos de registro desde esta red. Por favor, espera ' . $seconds . ' segundos.',
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'captcha' => 'required|string',
        ]);

        $sessionCaptcha = session('register_captcha');
        session()->forget('register_captcha');

        if (!$sessionCaptcha || strtolower($request->captcha) !== $sessionCaptcha) {
            throw ValidationException::withMessages([
                'captcha' => 'El código de seguridad (Captcha) es incorrecto.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        \App\Models\SecurityLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'email' => $user->email,
            'event' => 'Registro de Cuenta Nueva',
            'status' => 'Exitoso',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
