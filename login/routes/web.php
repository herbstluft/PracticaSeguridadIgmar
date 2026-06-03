<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Models\SecurityLog;
use App\Models\User;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas Web de la Aplicación
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas principales para la aplicación. Estas
| rutas son cargadas por el RouteServiceProvider dentro de un grupo que
| contiene el grupo de middleware "web".
|
*/

Route::middleware('slack.log')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Sembrar de forma dinámica algunos registros de seguridad iniciales si la tabla está vacía
        if (SecurityLog::count() === 0) {
            $admin = User::where('role', 'admin')->first();
            $normalUser = User::where('role', 'user')->first();
            $guest = User::where('role', 'guest')->first();

            SecurityLog::create([
                'user_id' => $admin ? $admin->id : null,
                'ip_address' => fake()->ipv4,
                'email' => $admin ? $admin->email : 'admin@seguridad.com',
                'event' => 'Autenticación Exitosa (3 Pasos)',
                'status' => 'Autorizado',
            ]);

            SecurityLog::create([
                'user_id' => $normalUser ? $normalUser->id : null,
                'ip_address' => fake()->ipv4,
                'email' => $normalUser ? $normalUser->email : 'usuario@seguridad.com',
                'event' => 'Intento 2FA Inválido',
                'status' => 'Rechazado',
            ]);

            SecurityLog::create([
                'user_id' => $guest ? $guest->id : null,
                'ip_address' => fake()->ipv4,
                'email' => $guest ? $guest->email : 'invitado@seguridad.com',
                'event' => 'Código OTP Expirado',
                'status' => 'Rechazado',
            ]);
        }

        $data = [];

        if ($user->role === 'admin') {
            $data['users'] = User::select('name', 'email', 'role', 'two_factor_confirmed_at')->get()->map(function ($u) {
                return [
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'mfa' => $u->two_factor_confirmed_at ? 'Activo' : 'Inactivo',
                ];
            });
            
            $data['securityLogs'] = SecurityLog::latest()->take(50)->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'ip' => $log->ip_address ?? '127.0.0.1',
                    'email' => $log->email ?? 'N/A',
                    'event' => $log->event,
                    'status' => $log->status,
                    'time' => $log->created_at->diffForHumans(),
                ];
            });
        } else {
            $data['personalLogs'] = SecurityLog::where('user_id', $user->id)
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'device' => $log->event,
                        'location' => $log->ip_address ?? '127.0.0.1',
                        'status' => $log->status,
                        'time' => $log->created_at->diffForHumans(),
                    ];
                });
        }

        return Inertia::render('Dashboard', $data);
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
});
