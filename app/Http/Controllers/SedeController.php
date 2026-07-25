<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    /**
     * Guardar una nueva sede.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:sedes,code',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre de la sede es obligatorio.',
            'code.unique' => 'Este código de sede ya existe.',
        ]);

        $code = $request->code;
        if (empty($code)) {
            $nextId = Sede::max('id') + 1;
            $code = 'SEDE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        Sede::create([
            'code' => strtoupper(trim($code)),
            'name' => trim($request->name),
            'description' => $request->description,
        ]);

        return back()->with('success', 'Sede creada correctamente.');
    }

    /**
     * Actualizar una sede existente.
     */
    public function update(Request $request, Sede $sede)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sedes,code,' . $sede->id,
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre de la sede es obligatorio.',
            'code.required' => 'El código de la sede es obligatorio.',
            'code.unique' => 'Este código de sede ya está asignado a otra sede.',
        ]);

        $oldName = $sede->name;

        $sede->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description,
        ]);

        // Sincronizar el nombre en la tabla users para consistencia
        User::where('sede_id', $sede->id)->update(['sede' => $sede->name]);

        // Gestionar vinculación / desvinculación de usuarios
        if ($request->has('users_managed')) {
            $selectedUserIds = array_map('intval', $request->input('user_ids', []));

            // Quitar la sede a usuarios desmarcados
            User::where('sede_id', $sede->id)
                ->whereNotIn('id', $selectedUserIds)
                ->update([
                    'sede_id' => null,
                    'sede' => null,
                ]);

            // Asignar la sede a usuarios marcados
            if (!empty($selectedUserIds)) {
                User::whereIn('id', $selectedUserIds)->update([
                    'sede_id' => $sede->id,
                    'sede' => $sede->name,
                ]);
            }
        }

        return back()->with('success', "La sede '{$oldName}' y sus usuarios asignados se han actualizado correctamente.");
    }

    /**
     * Eliminar una sede.
     */
    public function destroy(Sede $sede)
    {
        $sedeName = $sede->name;

        // Desvincular usuarios asociados antes de borrar la sede
        User::where('sede_id', $sede->id)->update([
            'sede_id' => null,
            'sede' => null,
        ]);

        $sede->delete();

        return back()->with('success', "La sede '{$sedeName}' fue eliminada correctamente.");
    }
}
