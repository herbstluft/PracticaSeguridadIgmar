<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SecurityLog;

class DatabaseSeeder extends Seeder
{
    /**
     * Sembrar la base de datos de la aplicación.
     */
    public function run(): void
    {
        // Sembrar registros iniciales de seguridad si la tabla está vacía y los usuarios existen
        if (SecurityLog::count() === 0) {
            $admin = User::where('role', 'admin')->first();
            $normalUser = User::where('role', 'user')->first();
            $guest = User::where('role', 'guest')->first();

            if ($admin) {
                SecurityLog::create([
                    'user_id' => $admin->id,
                    'ip_address' => fake()->ipv4,
                    'email' => $admin->email,
                    'event' => 'Autenticación Exitosa (3 Pasos)',
                    'status' => 'Autorizado',
                    'created_at' => now()->subHours(2),
                ]);
            }

            if ($normalUser) {
                SecurityLog::create([
                    'user_id' => $normalUser->id,
                    'ip_address' => fake()->ipv4,
                    'email' => $normalUser->email,
                    'event' => 'Intento 2FA Inválido',
                    'status' => 'Rechazado',
                    'created_at' => now()->subHour(),
                ]);
            }

            if ($guest) {
                SecurityLog::create([
                    'user_id' => $guest->id,
                    'ip_address' => fake()->ipv4,
                    'email' => $guest->email,
                    'event' => 'Código OTP Expirado',
                    'status' => 'Rechazado',
                    'created_at' => now()->subMinutes(30),
                ]);
            }
        }
    }
}
