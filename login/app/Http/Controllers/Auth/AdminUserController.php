<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Cambiar el rol de un usuario pidiendo confirmación de contraseña del administrador.
     */
    public function changeRole(Request $request)
    {
        // 1. Validar que el usuario autenticado sea administrador
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json([
                'message' => 'Acceso denegado. No tienes permisos de administrador.'
            ], 403);
        }

        // 2. Validar la petición
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,user,guest',
            'password' => 'required|string',
        ], [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.exists' => 'El usuario no existe.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña del administrador es obligatoria.',
        ]);

        // 3. Validar la contraseña del administrador (Autenticación Step-Up)
        if (!Hash::check($request->password, $admin->password)) {
            $targetUser = User::find($request->user_id);
            $targetEmail = $targetUser ? $targetUser->email : 'N/A';

            SecurityLog::create([
                'user_id' => $admin->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'email' => $admin->email,
                'event' => "Intento fallido de Cambio de Rol (Destino: {$targetEmail})",
                'status' => 'Rechazado',
            ]);

            return response()->json([
                'message' => 'La contraseña de administrador ingresada es incorrecta.'
            ], 422);
        }

        // 4. Evitar que el administrador se cambie el rol a sí mismo
        if ($admin->id == $request->user_id) {
            return response()->json([
                'message' => 'No puedes cambiar tu propio rol administrativo.'
            ], 422);
        }

        // 5. Proceder al cambio de rol
        $targetUser = User::findOrFail($request->user_id);
        $oldRole = $targetUser->role;
        $targetUser->role = $request->role;
        $targetUser->save();

        // 6. Registrar en la bitácora SIEM
        SecurityLog::create([
            'user_id' => $admin->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $admin->email,
            'event' => "Cambio de Rol de Usuario (Destino: {$targetUser->email}, de {$oldRole} a {$request->role})",
            'status' => 'Exitoso',
        ]);

        return response()->json([
            'message' => 'El rol del usuario ha sido actualizado con éxito.'
        ]);
    }

    /**
     * Restablecer los factores de autenticación (MFA/2FA) de un usuario.
     */
    public function resetMfa(Request $request)
    {
        // 1. Validar que el usuario autenticado sea administrador
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json([
                'message' => 'Acceso denegado. No tienes permisos de administrador.'
            ], 403);
        }

        // 2. Validar la petición
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required|string',
        ], [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.exists' => 'El usuario no existe.',
            'password.required' => 'La contraseña del administrador es obligatoria.',
        ]);

        // 3. Validar la contraseña del administrador (Autenticación Step-Up)
        if (!Hash::check($request->password, $admin->password)) {
            $targetUser = User::find($request->user_id);
            $targetEmail = $targetUser ? $targetUser->email : 'N/A';

            SecurityLog::create([
                'user_id' => $admin->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'email' => $admin->email,
                'event' => "Intento fallido de Restablecer MFA (Destino: {$targetEmail})",
                'status' => 'Rechazado',
            ]);

            return response()->json([
                'message' => 'La contraseña de administrador ingresada es incorrecta.'
            ], 422);
        }

        // 4. Evitar que el administrador restablezca su propio MFA desde aquí para evitar bloqueos
        if ($admin->id == $request->user_id) {
            return response()->json([
                'message' => 'No puedes restablecer tu propio MFA desde este panel.'
            ], 422);
        }

        // 5. Restablecer MFA
        $targetUser = User::findOrFail($request->user_id);
        $targetUser->two_factor_secret = null;
        $targetUser->two_factor_confirmed_at = null;
        $targetUser->backup_codes = null;
        $targetUser->save();

        // 6. Registrar en la bitácora SIEM
        SecurityLog::create([
            'user_id' => $admin->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $admin->email,
            'event' => "Restablecimiento de MFA de Usuario (Destino: {$targetUser->email})",
            'status' => 'Exitoso',
        ]);

        return response()->json([
            'message' => 'Los factores de autenticación (MFA/2FA) del usuario han sido restablecidos con éxito.'
        ]);
    }
}
