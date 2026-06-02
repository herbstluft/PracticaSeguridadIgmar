<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_guest_can_authenticate_immediately_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'guest']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_requires_otp_to_authenticate(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Paso 1: Login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/verify-otp?email=' . urlencode($user->email));

        // Obtener el código OTP guardado en la base de datos
        $user->refresh();
        $this->assertNotNull($user->otp_code);

        // Paso 2: Verificar OTP
        $otpResponse = $this->withSession(['auth.temp_user_id' => $user->id])
            ->post('/verify-otp', [
                'email' => $user->email,
                'code' => $user->otp_code,
            ]);

        $this->assertAuthenticatedAs($user);
        $otpResponse->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_admin_requires_otp_and_2fa_to_authenticate(): void
    {
        $backupCode = '1234-ABCD';
        $user = User::factory()->create([
            'role' => 'admin',
            'two_factor_secret' => 'LUTM7T7P7P7P7P7P',
            'two_factor_confirmed_at' => now(),
            'backup_codes' => [\Illuminate\Support\Facades\Hash::make($backupCode)],
        ]);

        // Paso 1: Login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/verify-otp?email=' . urlencode($user->email));

        // Obtener el código OTP guardado en la base de datos
        $user->refresh();
        $this->assertNotNull($user->otp_code);

        // Paso 2: Verificar OTP
        $otpResponse = $this->withSession(['auth.temp_user_id' => $user->id])
            ->post('/verify-otp', [
                'email' => $user->email,
                'code' => $user->otp_code,
            ]);

        $this->assertGuest();
        $otpResponse->assertRedirect('/verify-2fa');

        // Paso 3: Verificar 2FA (Usando código de respaldo)
        $twoFactorResponse = $this->withSession([
                'auth.temp_user_id' => $user->id,
                'auth.otp_verified' => true,
            ])
            ->post('/verify-2fa', [
                'code' => $backupCode,
            ]);

        $this->assertAuthenticatedAs($user);
        $twoFactorResponse->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
