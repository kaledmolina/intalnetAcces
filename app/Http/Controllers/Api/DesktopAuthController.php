<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DesktopAuthController extends Controller
{
    /**
     * Verificar credenciales y autorización de un usuario desde la aplicación de escritorio.
     * Este endpoint es consultado por las PCs para autorizar el inicio de sesión contra la nube.
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo es inválido.',
            'password.required' => 'La contraseña es requerida.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // 1. Validar existencia y contraseña contra la base de datos de la nube
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas. Este usuario no existe o la contraseña es incorrecta en la nube central.',
            ], 401);
        }

        // 2. Validar si el SuperAdmin lo tiene activo
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tu acceso a la versión de escritorio ha sido revocado o suspendido por el Superadministrador de Intalnet.',
            ], 403);
        }

        // 3. Generar token de sincronización seguro firmado
        $syncToken = hash_hmac('sha256', $user->email . '|' . $user->password, config('app.key'));

        // 4. Retornar datos autorizados para que la aplicación de PC sincronice el inicio de sesión
        return response()->json([
            'success' => true,
            'message' => 'Acceso autorizado exitosamente por la nube central.',
            'sync_token' => $syncToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password_hash' => $user->password,
                'is_superadmin' => (bool) $user->is_superadmin,
                'sede_id' => $user->sede_id,
                'sede' => $user->sede,
            ],
        ]);
    }
}
