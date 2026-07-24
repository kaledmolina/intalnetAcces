<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Mostrar el formulario de registro.
     */
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /**
     * Procesar el registro de un nuevo usuario/inquilino (SaaS Tenant).
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'El nombre de la empresa o usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo es inválido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $nextId = \App\Models\Sede::max('id') + 1;
        $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        $sede = \App\Models\Sede::create([
            'code' => $code,
            'name' => $validated['name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_superadmin' => false,
            'is_active' => true,
            'sede_id' => $sede->id,
            'sede' => $sede->name,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', '¡Registro exitoso! Bienvenido a IntalnetAcces.');
    }
}
