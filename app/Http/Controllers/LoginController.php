<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * Mostrar el formulario de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Procesar la autenticación del usuario.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo es inválido.',
            'password.required' => 'La contraseña es requerida.',
        ]);

        $remember = $request->has('remember');

        // Determinar si debemos validar contra la nube (Modo Escritorio NativePHP o activado por variable)
        $isDesktop = config('nativephp-internal.running') || env('NATIVEPHP_RUNNING', false);
        $cloudAuthEnabled = env('CLOUD_AUTH_ENABLED', true);

        if ($isDesktop && $cloudAuthEnabled) {
            $cloudUrl = rtrim(env('CLOUD_AUTH_URL', 'https://access.intalnet.com'), '/');

            try {
                // 1. Validar credenciales y autorización directamente en la nube
                $response = Http::timeout(10)->post("{$cloudUrl}/api/v1/desktop/verify-login", [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                ]);

                $data = $response->json();

                // 2. Si la nube deniega el acceso (usuario no existe, clave incorrecta o desactivado por el Superadmin)
                if (!$response->successful() || empty($data['success'])) {
                    if ($response->status() === 404) {
                        $errorMessage = 'La ruta de verificación en la nube (access.intalnet.com) aún no está activa. Ejecuta "git pull" y "php artisan route:clear" en el servidor web.';
                    } else {
                        $errorMessage = $data['message'] ?? 'Acceso denegado. Este usuario no está autorizado en la nube central (access.intalnet.com).';
                    }
                    return back()->withErrors(['email' => $errorMessage])->onlyInput('email');
                }

                $cloudUser = $data['user'];

                // 3. Sincronizar Sede local si viene indicada
                $sedeId = null;
                $sedeName = $cloudUser['sede'] ?? null;
                if (!empty($sedeName)) {
                    $sede = Sede::firstOrCreate(
                        ['name' => $sedeName],
                        ['code' => 'SEDE-' . str_pad($cloudUser['sede_id'] ?? 1, 3, '0', STR_PAD_LEFT)]
                    );
                    $sedeId = $sede->id;
                    $sedeName = $sede->name;
                }

                // 4. Sincronizar o crear el usuario en la base de datos local SQLite con los permisos de la nube
                $localUser = User::updateOrCreate(
                    ['email' => $cloudUser['email']],
                    [
                        'name' => $cloudUser['name'],
                        'password' => $cloudUser['password_hash'],
                        'is_superadmin' => (bool) ($cloudUser['is_superadmin'] ?? false),
                        'is_active' => true,
                        'sede_id' => $sedeId,
                        'sede' => $sedeName,
                    ]
                );

                // 5. Iniciar la sesión local
                Auth::login($localUser, $remember);
                $request->session()->regenerate();

                // 6. Almacenar el token de sincronización de la nube e importar datos iniciales
                if (!empty($data['sync_token'])) {
                    $cloudSyncService = app(\App\Services\CloudSyncService::class);
                    $cloudSyncService->storeCredentials($localUser->email, $data['sync_token']);

                    try {
                        $cloudSyncService->pull();
                    } catch (\Throwable $t) {
                        Log::warning('Error en pull inicial tras inicio de sesión: ' . $t->getMessage());
                    }
                }

                return redirect()->intended(route('dashboard'))
                    ->with('success', '¡Bienvenido, ' . $localUser->name . '! Sesión autorizada por la nube central.');

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                return back()->withErrors([
                    'email' => 'No fue posible validar tu acceso con la nube central (access.intalnet.com). Verifica que la computadora tenga conexión a internet.',
                ])->onlyInput('email');
            } catch (\Throwable $e) {
                Log::error('Error en Cloud Auth Desktop: ' . $e->getMessage());
                return back()->withErrors([
                    'email' => 'Error al validar con la nube: ' . $e->getMessage(),
                ])->onlyInput('email');
            }
        }

        // Modo Web estándar / Fallback local si no está en escritorio
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido desactivada por el administrador del sistema.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', '¡Bienvenido al panel, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cerrar la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }
}
