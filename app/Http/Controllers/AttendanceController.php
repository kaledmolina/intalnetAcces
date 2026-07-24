<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['employee', 'device']);

        // Filtro por fecha desde / hasta
        if ($request->filled('start_date')) {
            $query->whereDate('event_time', '>=', $request->input('start_date'));
        } else {
            // Por defecto mostrar desde hace 30 días
            $query->whereDate('event_time', '>=', now()->subDays(30)->toDateString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('event_time', '<=', $request->input('end_date'));
        }

        // Filtro por Empleado
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Filtro por Dispositivo / Huellero
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        }

        // Filtro por Solo Tardanzas
        if ($request->filled('only_late') && $request->input('only_late') == '1') {
            $query->where('is_late', true);
        }

        // Búsqueda rápida por nombre/código
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('employee_no', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->latest('event_time')->paginate(25)->withQueryString();
        $employees = Employee::orderBy('name')->get();
        $devices = Device::all();

        return view('attendance.index', compact('records', 'employees', 'devices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string',
            'event_time' => 'required|date',
            'device_id' => 'required|exists:devices,id',
        ]);

        $employee = Employee::where('employee_no', $validated['employee_no'])->first();
        $eventTime = Carbon::parse($validated['event_time']);

        $isLate = false;
        $lateMinutes = 0;

        if ($employee && $employee->schedule) {
            $dayConfig = $employee->schedule->days()->where('day_of_week', $eventTime->dayOfWeekIso)->first();

            if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                $entryTime = Carbon::parse($eventTime->toDateString() . ' ' . $dayConfig->entry_time);
                $entryTimeWithTolerance = $entryTime->copy()->addMinutes($employee->schedule->tolerance_minutes);

                if ($eventTime->greaterThan($entryTimeWithTolerance)) {
                    $isLate = true;
                    $lateMinutes = (int) $entryTime->diffInMinutes($eventTime);
                }
            }
        }

        AttendanceRecord::create([
            'employee_id' => $employee ? $employee->id : null,
            'employee_no' => $validated['employee_no'],
            'device_id' => $validated['device_id'],
            'event_time' => $eventTime,
            'event_type' => 'fingerprint',
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'raw_payload' => json_encode(['manual_test' => true]),
        ]);

        return redirect()->route('attendance.index')->with('success', 'Marcación de prueba registrada con éxito.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee', 'device']);

        if ($request->filled('start_date')) {
            $query->whereDate('event_time', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('event_time', '<=', $request->input('end_date'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }
        if ($request->filled('only_late') && $request->input('only_late') == '1') {
            $query->where('is_late', true);
        }

        $records = $query->latest('event_time')->get();

        $response = new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // BOM para Excel UTF-8
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezados
            fputcsv($handle, [
                'ID Empleado',
                'Nombre Empleado',
                'Fecha y Hora Marcación',
                'Huellero / Ubicación',
                'Tardanza',
                'Minutos Tardanza',
                'Tarjeta ID',
            ]);

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->employee_no,
                    $record->employee ? $record->employee->name : 'N/A',
                    $record->event_time->format('d/m/Y H:i:s'),
                    $record->device ? $record->device->name : 'Desconocido',
                    $record->is_late ? 'SI' : 'NO',
                    $record->late_minutes,
                    $record->card_no ?? 'N/A',
                ]);
            }

            fclose($handle);
        });

        $fileName = 'Reporte_Asistencia_' . now()->format('Y-m-d_H-i') . '.csv';

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
}
