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
        $data = [];

        if ($user->role === 'admin') {
            $search = request('search');
            $usersQuery = User::select('id', 'name', 'email', 'role', 'two_factor_confirmed_at', 'email_verified_at');
            
            if ($search) {
                $usersQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $paginatedUsers = $usersQuery->paginate(8)->withQueryString();
            
            $data['users'] = [
                'data' => $paginatedUsers->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role,
                        'mfa' => $u->two_factor_confirmed_at ? 'Activo' : 'Inactivo',
                        'email_otp' => in_array($u->role, ['admin', 'user']) ? 'Activo' : 'Inactivo',
                    ];
                })->all(),
                'links' => $paginatedUsers->linkCollection()->toArray(),
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'total' => $paginatedUsers->total(),
                'per_page' => $paginatedUsers->perPage(),
            ];

            $data['filters'] = [
                'search' => $search,
            ];
            
            $data['securityLogs'] = SecurityLog::latest()->take(50)->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'ip' => $log->ip_address ?? '127.0.0.1',
                    'user_agent' => $log->user_agent ?? 'Desconocido',
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
                        'user_agent' => $log->user_agent ?? 'Desconocido',
                        'status' => $log->status,
                        'time' => $log->created_at->diffForHumans(),
                    ];
                });
        }

        return Inertia::render('Dashboard', $data);
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::post('/admin/users/change-role', [\App\Http\Controllers\Auth\AdminUserController::class, 'changeRole'])
        ->middleware(['auth', 'verified'])
        ->name('admin.users.change-role');

    Route::post('/admin/users/reset-mfa', [\App\Http\Controllers\Auth\AdminUserController::class, 'resetMfa'])
        ->middleware(['auth', 'verified'])
        ->name('admin.users.reset-mfa');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
});
