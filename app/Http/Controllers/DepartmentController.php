<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')->orderBy('name')->get();
        $employees = \App\Models\Employee::orderBy('name')->get();
        return view('departments.index', compact('departments', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        Department::create($request->all());

        return back()->with('success', 'Departamento creado exitosamente.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
        ]);

        $department->update($request->all());

        return back()->with('success', 'Departamento actualizado exitosamente.');
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el departamento porque tiene empleados asignados.');
        }

        $department->delete();

        return back()->with('success', 'Departamento eliminado exitosamente.');
    }

    public function assignEmployees(Request $request, Department $department)
    {
        $request->validate([
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Primero, limpiamos el departamento a todos los empleados que actualmente lo tienen
        \App\Models\Employee::where('department_id', $department->id)->update(['department_id' => null]);

        // Luego, asignamos el departamento a los empleados seleccionados
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            \App\Models\Employee::whereIn('id', $request->employee_ids)->update(['department_id' => $department->id]);
        }

        return back()->with('success', 'Empleados actualizados exitosamente en el departamento.');
    }
}
