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

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

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
