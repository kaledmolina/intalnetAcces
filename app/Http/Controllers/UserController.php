<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios del sistema.
     */
    public function index()
    {
        $users = User::with('sedeRelation')->withCount('employees')->orderBy('id', 'desc')->paginate(10);

        $users->getCollection()->transform(function ($user) {
            if ($user->sede_id) {
                $tenantUserIds = User::where('sede_id', $user->sede_id)->pluck('id');
                $user->display_employees_count = \App\Models\Employee::withoutGlobalScope('tenant')
                    ->whereIn('user_id', $tenantUserIds)
                    ->count();
            } else {
                $user->display_employees_count = $user->employees_count;
            }
            return $user;
        });

        $allSedes = \App\Models\Sede::orderBy('name', 'asc')->get();
        $allUsers = User::orderBy('name', 'asc')->get();
        $allDepartments = \App\Models\Department::with('tenant')->orderBy('name', 'asc')->get();
        return view('users.index', compact('users', 'allSedes', 'allUsers', 'allDepartments'));
    }

    /**
     * Guardar un nuevo usuario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $sedeId = null;
        $sedeName = null;

        if ($request->filled('sede_id') && $request->sede_id !== 'none') {
            $sedeObj = \App\Models\Sede::find($request->sede_id);
            if ($sedeObj) {
                $sedeId = $sedeObj->id;
                $sedeName = $sedeObj->name;
            }
        } elseif ($request->filled('sede')) {
            $nameClean = trim($request->sede);
            $sedeObj = \App\Models\Sede::where('name', 'LIKE', $nameClean)->first();
            if (!$sedeObj) {
                $nextId = \App\Models\Sede::max('id') + 1;
                $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
                $sedeObj = \App\Models\Sede::create([
                    'code' => $code,
                    'name' => $nameClean,
                ]);
            }
            $sedeId = $sedeObj->id;
            $sedeName = $sedeObj->name;
        }

        $assignedDepartments = $request->input('department_ids', []);
        // Si viene el valor 'none' en el array (aunque con checkboxes no debería, pero por si acaso), lo limpiamos
        if (in_array('none', $assignedDepartments)) {
            $assignedDepartments = [];
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'sede_id' => $sedeId,
            'sede' => $sedeName,
            'assigned_departments' => $assignedDepartments,
            'password' => Hash::make($request->password),
            'is_superadmin' => $request->has('is_superadmin'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado por otro usuario.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $sedeId = null;
        $sedeName = null;

        if ($request->filled('sede_id') && $request->sede_id !== 'none') {
            $sedeObj = \App\Models\Sede::find($request->sede_id);
            if ($sedeObj) {
                $sedeId = $sedeObj->id;
                $sedeName = $sedeObj->name;
            }
        } elseif ($request->filled('sede')) {
            $nameClean = trim($request->sede);
            $sedeObj = \App\Models\Sede::where('name', 'LIKE', $nameClean)->first();
            if (!$sedeObj) {
                $nextId = \App\Models\Sede::max('id') + 1;
                $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
                $sedeObj = \App\Models\Sede::create([
                    'code' => $code,
                    'name' => $nameClean,
                ]);
            }
            $sedeId = $sedeObj->id;
            $sedeName = $sedeObj->name;
        }

        $assignedDepartments = $request->input('department_ids', []);
        if (in_array('none', $assignedDepartments)) {
            $assignedDepartments = [];
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'sede_id' => $sedeId,
            'sede' => $sedeName,
            'assigned_departments' => $assignedDepartments,
        ];

        // Solo cambiar la contraseña si se ingresó una nueva
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Evitar que el superadmin se quite el rol de superadmin o se desactive a sí mismo
        if ($user->id !== Auth::id()) {
            $data['is_superadmin'] = $request->has('is_superadmin');
            $data['is_active'] = $request->has('is_active');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Eliminar un usuario.
     */
    public function destroy(User $user)
    {
        // Evitar eliminar al usuario activo
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $userName = $user->name;
        $sedeId = $user->sede_id;

        // 1. Eliminar marcaciones, empleados, departamentos, horarios y dispositivos del usuario
        \App\Models\AttendanceRecord::withoutGlobalScope('tenant')->where('user_id', $user->id)->delete();
        \App\Models\Employee::withoutGlobalScope('tenant')->where('user_id', $user->id)->delete();
        \App\Models\Department::withoutGlobalScope('tenant')->where('user_id', $user->id)->delete();
        \App\Models\Schedule::withoutGlobalScope('tenant')->where('user_id', $user->id)->delete();
        \App\Models\Device::withoutGlobalScope('tenant')->where('user_id', $user->id)->delete();

        // 2. Eliminar la cuenta del usuario
        $user->delete();

        // 3. Si la sede no está siendo utilizada por ningún otro usuario, eliminar la sede
        if ($sedeId && User::where('sede_id', $sedeId)->count() === 0) {
            \App\Models\Sede::where('id', $sedeId)->delete();
        }

        return redirect()->route('users.index')->with('success', "El usuario '{$userName}', su sede y todos sus datos asociados fueron eliminados correctamente.");
    }

    /**
     * Alternar el estado activo/inactivo de un usuario.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusStr = $user->is_active ? 'activada' : 'desactivada';
        return back()->with('success', "La cuenta de '{$user->name}' ha sido {$statusStr} correctamente.");
    }

    /**
     * Actualizar el nombre de empresa / perfil del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'El nombre de la empresa / usuario es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'El nombre de la empresa / perfil se ha actualizado correctamente.');
    }

    /**
     * Asignar o cambiar la sede de un usuario.
     */
    public function assignSede(Request $request, User $user)
    {
        $request->validate([
            'sede_id' => 'nullable|exists:sedes,id',
            'new_sede_name' => 'nullable|string|max:255',
        ]);

        // Prioridad 1: Si se seleccionó una sede_id existente en el desplegable
        if ($request->filled('sede_id')) {
            $sede = \App\Models\Sede::findOrFail($request->sede_id);
            $user->update([
                'sede_id' => $sede->id,
                'sede' => $sede->name,
            ]);
            return back()->with('success', "Sede '{$sede->name}' [{$sede->code}] asignada correctamente a {$user->name}.");
        }

        // Prioridad 2: Si se ingresó el nombre de una nueva sede
        if ($request->filled('new_sede_name')) {
            $nameClean = trim($request->new_sede_name);
            
            // Buscar si ya existe una sede con este nombre
            $existingSede = \App\Models\Sede::where('name', 'LIKE', $nameClean)->first();
            if ($existingSede) {
                $user->update([
                    'sede_id' => $existingSede->id,
                    'sede' => $existingSede->name,
                ]);
                return back()->with('success', "Sede '{$existingSede->name}' [{$existingSede->code}] asignada a {$user->name}.");
            }

            $nextId = \App\Models\Sede::max('id') + 1;
            $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            $sede = \App\Models\Sede::create([
                'code' => $code,
                'name' => $nameClean,
            ]);
            $user->update([
                'sede_id' => $sede->id,
                'sede' => $sede->name,
            ]);
            return back()->with('success', "Nueva sede '{$sede->name}' [{$sede->code}] creada y asignada a {$user->name}.");
        }

        return back()->with('error', 'Por favor selecciona o escribe el nombre de una sede.');
    }

    /**
     * Permite a un usuario registrar exclusivamente su propia nueva sede cuando está sin sede.
     */
    public function registerMySede(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'sede_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'sede_name.required' => 'El nombre de la sede es obligatorio.',
        ]);

        $nameClean = trim($request->sede_name);
        $nextId = \App\Models\Sede::max('id') + 1;
        $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $sedeObj = \App\Models\Sede::create([
            'code' => $code,
            'name' => $nameClean,
            'description' => $request->description,
        ]);

        $user->update([
            'sede_id' => $sedeObj->id,
            'sede' => $sedeObj->name,
        ]);

        return back()->with('success', "¡Excelente! Tu sede '{$sedeObj->name}' [{$code}] ha sido registrada correctamente.");
    }
}
