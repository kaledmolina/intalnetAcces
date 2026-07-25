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

    public function show(Employee $employee)
    {
        $attendanceRecords = $employee->attendanceRecords()
            ->with('device')
            ->latest('event_time')
            ->paginate(20, ['*'], 'punches_page');

        $dailyHours = $employee->attendanceRecords()
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

        foreach ($dailyHours as $row) {
            $checkIn = $row->explicit_check_in ? \Carbon\Carbon::parse($row->explicit_check_in) : \Carbon\Carbon::parse($row->fallback_check_in);
            $checkOut = $row->explicit_check_out ? \Carbon\Carbon::parse($row->explicit_check_out) : \Carbon\Carbon::parse($row->fallback_check_out);
            
            $row->check_in_time = $checkIn->format('h:i A');
            $diffMinutes = $checkIn->diffInMinutes($checkOut);
            
            $hasExplicitCheckOut = !empty($row->explicit_check_out);
            $hasMultiplePunches = $row->punch_count > 1;
            
            // Si el usuario marcó la salida explícitamente en el dispositivo, la respetamos.
            // Si no lo hizo pero tiene múltiples marcas de más de 30 minutos, la asignamos como salida automática.
            if ($hasExplicitCheckOut || ($hasMultiplePunches && $diffMinutes >= 30 && $row->fallback_check_out !== $row->fallback_check_in)) {
                $row->check_out_time = $checkOut->format('h:i A');
                $hours = floor($diffMinutes / 60);
                $minutes = $diffMinutes % 60;
                $row->hours_worked_text = "{$hours}h {$minutes}m";
                $row->hours_worked_decimal = number_format($diffMinutes / 60, 2);
            } else {
                $row->check_out_time = 'Falta Salida';
                $row->hours_worked_text = '—';
                $row->hours_worked_decimal = '0.00';
            }
        }

        $currentSchedule = $employee->currentSchedule();
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
