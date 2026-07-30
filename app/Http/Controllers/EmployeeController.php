<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\EmployeeSchedule;
use App\Services\HikvisionService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('department');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhere('document_id', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('filter_fingerprint')) {
            if ($request->input('filter_fingerprint') === 'missing') {
                $query->where('has_fingerprint', false);
            } elseif ($request->input('filter_fingerprint') === 'registered') {
                $query->where('has_fingerprint', true);
            }
        }

        $employees = $query->orderBy('employee_no')->paginate(15);
        $departments = Department::orderBy('name')->get();
        $devices = Device::all();

        return view('employees.index', compact('employees', 'departments', 'devices'));
    }

    public function create()
    {
        $schedules = Schedule::all();
        $departments = Department::orderBy('name')->get();
        $devices = Device::all();
        return view('employees.create', compact('schedules', 'departments', 'devices'));
    }

    public function store(Request $request, HikvisionService $hikvisionService)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|unique:employees,employee_no',
            'name' => 'required|string|max:255',
            'document_id' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'schedule_id' => 'nullable|exists:schedules,id',
            'device_id' => 'nullable|exists:devices,id',
        ]);

        $employee = Employee::create([
            'employee_no' => $validated['employee_no'],
            'name' => $validated['name'],
            'document_id' => $validated['document_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($validated['schedule_id'])) {
            EmployeeSchedule::create([
                'employee_id' => $employee->id,
                'schedule_id' => $validated['schedule_id'],
                'start_date' => now()->toDateString(),
            ]);
        }

        if (!empty($validated['device_id'])) {
            $device = Device::find($validated['device_id']);
            if ($device) {
                $hikvisionService->pushUserToDevice($device, $employee->employee_no, $employee->name);
                $result = $hikvisionService->captureFingerprintFromDevice($device, $employee->employee_no);

                return redirect()->route('employees.index')->with(
                    'success',
                    "Empleado registrado. Usuario enviado a {$device->name}. " . $result['message']
                );
            }
        }

        return redirect()->route('employees.index')->with('success', "Empleado {$employee->name} (#{$employee->employee_no}) registrado con éxito.");
    }

    public function captureFingerprint(Request $request, HikvisionService $hikvisionService)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string',
            'name' => 'required|string',
            'device_id' => 'required|exists:devices,id',
        ]);

        $device = Device::findOrFail($validated['device_id']);

        $hikvisionService->pushUserToDevice($device, $validated['employee_no'], $validated['name']);
        $result = $hikvisionService->captureFingerprintFromDevice($device, $validated['employee_no']);

        return response()->json($result);
    }

    public function confirmFingerprint(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string',
        ]);

        $employee = Employee::where('employee_no', (string) $validated['employee_no'])->first();

        if ($employee) {
            $employee->update(['has_fingerprint' => true]);
            return response()->json(['success' => true, 'message' => 'Huella confirmada y registrada en la base de datos.']);
        }

        return response()->json(['success' => false, 'message' => 'Empleado no encontrado.'], 404);
    }

    public function checkFingerprintStatus(Request $request, HikvisionService $hikvisionService)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string',
            'device_id' => 'nullable|exists:devices,id',
        ]);

        $device = Device::find($validated['device_id']) ?? Device::first();

        if (!$device) {
            return response()->json(['registered' => false]);
        }

        $registered = $hikvisionService->checkFingerprintStatus($device, $validated['employee_no']);

        return response()->json([
            'registered' => $registered,
            'employee_no' => $validated['employee_no'],
            'device' => $device->name,
        ]);
    }

    public function importFromDevices(HikvisionService $hikvisionService)
    {
        $result = $hikvisionService->importUsersFromAllDevices();

        return redirect()->route('employees.index')->with(
            'success',
            "Importación desde Huelleros finalizada: {$result['imported']} empleados importados nuevos, {$result['updated']} nombres actualizados."
        );
    }

    public function show(Request $request, Employee $employee)
    {
        $attendanceQuery = $employee->attendanceRecords();
        $dailyHoursQuery = $employee->attendanceRecords();

        if ($request->filled('date')) {
            $filterDate = $request->input('date');
            $attendanceQuery->whereDate('event_time', $filterDate);
            $dailyHoursQuery->whereDate('event_time', $filterDate);
        }

        $attendanceRecords = $attendanceQuery
            ->with('device')
            ->latest('event_time')
            ->paginate(20, ['*'], 'punches_page');

        $dailyHours = $dailyHoursQuery
            ->selectRaw("
                DATE(event_time) as event_date,
                MIN(CASE WHEN attendance_type = 'check_in' THEN event_time ELSE NULL END) as explicit_check_in,
                MAX(CASE WHEN attendance_type = 'check_out' THEN event_time ELSE NULL END) as explicit_check_out,
                MIN(event_time) as fallback_check_in,
                MAX(event_time) as fallback_check_out,
                COUNT(*) as punch_count
            ")
            ->groupBy('event_date')
            ->orderBy('event_date', 'desc')
            ->paginate(15, ['*'], 'hours_page');

        $currentSchedule = $employee->currentSchedule();

        foreach ($dailyHours as $row) {
            $punches = $employee->attendanceRecords()
                ->whereDate('event_time', $row->event_date)
                ->orderBy('event_time')
                ->get();

            $dayOfWeek = \Carbon\Carbon::parse($row->event_date)->dayOfWeekIso;
            $dayConfig = clone $currentSchedule ? clone $currentSchedule->days->where('day_of_week', $dayOfWeek)->first() : null;

            $totalWorkedMinutes = 0;
            $extraMinutes = 0;
            $notes = [];

            if ($punches->count() >= 2) {
                $firstPunch = clone $punches->first()->event_time;
                $lastPunch = clone $punches->last()->event_time;

                $row->check_in_time = $firstPunch->format('h:i A');
                
                // Si la diferencia es menor a 30 mins y no marcó explicitamente checkout, no lo contamos como salida válida (evita doble marcación rápida)
                $diffMinutes = $firstPunch->diffInMinutes($lastPunch);
                $hasExplicitCheckOut = !empty($row->explicit_check_out);
                
                if ($hasExplicitCheckOut || $diffMinutes >= 30) {
                    $row->check_out_time = $lastPunch->format('h:i A');

                    if ($dayConfig && $dayConfig->is_working_day) {
                        $schEntry = \Carbon\Carbon::parse($row->event_date . ' ' . $dayConfig->entry_time);
                        $schExit = \Carbon\Carbon::parse($row->event_date . ' ' . $dayConfig->exit_time);

                        // Horas Extras (Llegada temprana)
                        if ($firstPunch->lt($schEntry)) {
                            $extraMinutes += $firstPunch->diffInMinutes($schEntry);
                        }
                        // Horas Extras (Salida tardía)
                        if ($lastPunch->gt($schExit)) {
                            $extraMinutes += $schExit->diffInMinutes($lastPunch);
                        }

                        if ($dayConfig->break_start_time && $dayConfig->break_end_time) {
                            $schLunchStart = \Carbon\Carbon::parse($row->event_date . ' ' . $dayConfig->break_start_time);
                            $schLunchEnd = \Carbon\Carbon::parse($row->event_date . ' ' . $dayConfig->break_end_time);
                            $scheduledLunchMinutes = $schLunchStart->diffInMinutes($schLunchEnd);

                            if ($punches->count() >= 4) {
                                // Tiene 4 marcaciones o más, tomamos la 2da como salida de almuerzo y la penúltima como regreso
                                // O más preciso, la primera como entrada, la última como salida y las intermedias para almuerzo.
                                $lunchOut = clone $punches[1]->event_time;
                                $lunchIn = clone $punches[$punches->count() - 2]->event_time;

                                $morningMinutes = $firstPunch->diffInMinutes($lunchOut);
                                $afternoonMinutes = $lunchIn->diffInMinutes($lastPunch);

                                $totalWorkedMinutes = $morningMinutes + $afternoonMinutes;
                            } else {
                                // Menos de 4 huellas, no reportó almuerzo. Descontamos el tiempo programado.
                                $totalWorkedMinutes = $diffMinutes - $scheduledLunchMinutes;
                                if ($totalWorkedMinutes < 0) $totalWorkedMinutes = 0;
                                $notes[] = "⚠️ Hora de almuerzo no reportada en el huellero";
                            }
                        } else {
                            $totalWorkedMinutes = $diffMinutes;
                        }
                    } else {
                        // Día no laborable, todo el tiempo es extra
                        $totalWorkedMinutes = $diffMinutes;
                        $extraMinutes = $diffMinutes;
                        $notes[] = "⚠️ Día no laborable según horario";
                    }
                } else {
                    $row->check_out_time = 'Falta Salida';
                    $totalWorkedMinutes = 0;
                }
            } else {
                $row->check_in_time = $punches->first() ? $punches->first()->event_time->format('h:i A') : 'Sin Entrada';
                $row->check_out_time = 'Falta Salida';
                $totalWorkedMinutes = 0;
            }

            if ($totalWorkedMinutes > 0) {
                $hours = floor($totalWorkedMinutes / 60);
                $minutes = $totalWorkedMinutes % 60;
                $row->hours_worked_text = "{$hours}h {$minutes}m";
                $row->hours_worked_decimal = number_format($totalWorkedMinutes / 60, 2);
            } else {
                $row->hours_worked_text = '—';
                $row->hours_worked_decimal = '0.00';
            }

            if ($extraMinutes > 0) {
                $eHours = floor($extraMinutes / 60);
                $eMins = $extraMinutes % 60;
                $row->extra_hours_text = "{$eHours}h {$eMins}m";
            } else {
                $row->extra_hours_text = null;
            }

            $row->notes = $notes;
        }

        $schedules = Schedule::all();

        return view('employees.show', compact('employee', 'attendanceRecords', 'dailyHours', 'currentSchedule', 'schedules'));
    }

    public function edit(Employee $employee)
    {
        $schedules = Schedule::all();
        $departments = Department::orderBy('name')->get();
        $devices = Device::all();
        $currentSchedule = $employee->currentSchedule();
        return view('employees.edit', compact('employee', 'schedules', 'departments', 'devices', 'currentSchedule'));
    }

    public function update(Request $request, Employee $employee, HikvisionService $hikvisionService)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|unique:employees,employee_no,' . $employee->id,
            'name' => 'required|string|max:255',
            'document_id' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'required|boolean',
            'schedule_id' => 'nullable|exists:schedules,id',
            'device_id' => 'nullable|exists:devices,id',
        ]);

        $employee->update([
            'employee_no' => $validated['employee_no'],
            'name' => $validated['name'],
            'document_id' => $validated['document_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        if (isset($validated['schedule_id'])) {
            $currentSchedule = $employee->currentSchedule();
            if (!$currentSchedule || $currentSchedule->id != $validated['schedule_id']) {
                EmployeeSchedule::create([
                    'employee_id' => $employee->id,
                    'schedule_id' => $validated['schedule_id'],
                    'start_date' => now()->toDateString(),
                ]);
            }
        }

        if (!empty($validated['device_id'])) {
            $device = Device::find($validated['device_id']);
            if ($device) {
                $hikvisionService->pushUserToDevice($device, $employee->employee_no, $employee->name);
            }
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Datos del empleado actualizados y sincronizados con el huellero.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Empleado eliminado.');
    }
}
