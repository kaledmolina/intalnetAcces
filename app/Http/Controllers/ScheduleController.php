<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with(['days', 'employeeSchedules', 'departments'])
            ->withCount(['employeeSchedules', 'departments'])
            ->get();
        
        $departments = \App\Models\Department::orderBy('name')->get();
        return view('schedules.index', compact('schedules', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tolerance_minutes' => 'required|integer|min:0|max:120',
            'is_default' => 'nullable|boolean',
            'check_break_tardiness' => 'nullable|boolean',
            'days' => 'required|array|size:7',
        ]);

        $isDefault = $request->has('is_default');

        if ($isDefault) {
            Schedule::query()->update(['is_default' => false]);
        }

        $schedule = Schedule::create([
            'name' => $validated['name'],
            'tolerance_minutes' => $validated['tolerance_minutes'],
            'is_default' => $isDefault,
            'check_break_tardiness' => $request->has('check_break_tardiness'),
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $dayData = $request->input("days.$i", []);
            $isWorking = isset($dayData['is_working_day']);
            
            $schedule->days()->create([
                'day_of_week' => $i,
                'is_working_day' => $isWorking,
                'entry_time' => $isWorking ? ($dayData['entry_time'] ?? null) : null,
                'break_start_time' => $isWorking ? ($dayData['break_start_time'] ?? null) : null,
                'break_end_time' => $isWorking ? ($dayData['break_end_time'] ?? null) : null,
                'exit_time' => $isWorking ? ($dayData['exit_time'] ?? null) : null,
            ]);
        }

        return redirect()->route('schedules.index')->with('success', 'Horario creado correctamente.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tolerance_minutes' => 'required|integer|min:0|max:120',
            'is_default' => 'nullable|boolean',
            'check_break_tardiness' => 'nullable|boolean',
            'days' => 'required|array|size:7',
        ]);

        $isDefault = $request->has('is_default');

        if ($isDefault) {
            Schedule::where('id', '!=', $schedule->id)->update(['is_default' => false]);
        }

        $schedule->update([
            'name' => $validated['name'],
            'tolerance_minutes' => $validated['tolerance_minutes'],
            'is_default' => $isDefault,
            'check_break_tardiness' => $request->has('check_break_tardiness'),
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $dayData = $request->input("days.$i", []);
            $isWorking = isset($dayData['is_working_day']);
            
            $schedule->days()->updateOrCreate(
                ['day_of_week' => $i],
                [
                    'is_working_day' => $isWorking,
                    'entry_time' => $isWorking ? ($dayData['entry_time'] ?? null) : null,
                    'break_start_time' => $isWorking ? ($dayData['break_start_time'] ?? null) : null,
                    'break_end_time' => $isWorking ? ($dayData['break_end_time'] ?? null) : null,
                    'exit_time' => $isWorking ? ($dayData['exit_time'] ?? null) : null,
                ]
            );
        }

        return redirect()->route('schedules.index')->with('success', 'Horario actualizado correctamente.');
    }

    public function destroy(Schedule $schedule)
    {
        if ($schedule->is_default) {
            return redirect()->route('schedules.index')->with('error', 'No se puede eliminar el horario por defecto.');
        }

        $schedule->delete();
        return redirect()->route('schedules.index')->with('success', 'Horario eliminado.');
    }

    public function assignDepartment(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $department = \App\Models\Department::findOrFail($validated['department_id']);
        $department->update(['schedule_id' => $schedule->id]);

        // Eliminar horarios individuales de los empleados de este departamento
        // para garantizar que todos usen estrictamente el horario del departamento
        $employeeIds = $department->employees()->pluck('id');
        if ($employeeIds->count() > 0) {
            \App\Models\EmployeeSchedule::whereIn('employee_id', $employeeIds)->delete();
        }

        return redirect()->back()->with('success', "Horario '{$schedule->name}' asignado al departamento '{$department->name}' y a todos sus empleados de forma unificada.");
    }
}
