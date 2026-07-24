<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use App\Services\HikvisionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $totalEmployees = Employee::where('is_active', true)->count();

        // Empleados que marcaron hoy
        $todayRecordEmpIds = AttendanceRecord::whereDate('event_time', $today)
            ->pluck('employee_id')
            ->unique()
            ->filter();

        $presentCount = $todayRecordEmpIds->count();
        $absentCount = max(0, $totalEmployees - $presentCount);

        // Tardanzas de hoy
        $lateTodayCount = AttendanceRecord::whereDate('event_time', $today)
            ->where('is_late', true)
            ->pluck('employee_id')
            ->unique()
            ->count();

        // Dispositivos y su estado
        $devices = Device::all();

        $devicesData = $devices->map(function ($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'ip_address' => $d->ip_address,
                'location' => $d->location,
                'status' => $d->status,
                'last_sync' => $d->last_sync_at ? $d->last_sync_at->diffForHumans() : 'Sin sincronizar',
            ];
        });

        // Generar datos para el gráfico de Recharts (últimos 7 días)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayLabel = $date->isoFormat('ddd D');

            $puntuales = AttendanceRecord::whereDate('event_time', $date)
                ->where('is_late', false)
                ->pluck('employee_id')
                ->unique()
                ->count();

            $tardanzas = AttendanceRecord::whereDate('event_time', $date)
                ->where('is_late', true)
                ->pluck('employee_id')
                ->unique()
                ->count();

            $chartData[] = [
                'day' => ucfirst($dayLabel),
                'Puntuales' => $puntuales,
                'Tardanzas' => $tardanzas,
            ];
        }

        // Lista de empleados paginada con su estado de hoy
        $employeesPaginated = Employee::where('is_active', true)->paginate(10);

        $paginatedIds = $employeesPaginated->pluck('id');
        $todayPunchesPaginated = AttendanceRecord::whereDate('event_time', $today)
            ->whereIn('employee_id', $paginatedIds)
            ->orderBy('event_time', 'asc')
            ->get()
            ->groupBy('employee_id');

        $employeesPaginated->getCollection()->transform(function ($emp) use ($todayPunchesPaginated) {
            $punch = $todayPunchesPaginated->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : null;
            $emp->today_is_late = $punch ? $punch->is_late : false;
            $emp->today_late_minutes = $punch ? $punch->late_minutes : 0;
            return $emp;
        });

        // Colecciones de empleados detalladas para interactividad en el Dashboard
        $totalEmployeesList = Employee::where('is_active', true)->get();

        $todayPunches = AttendanceRecord::whereDate('event_time', $today)
            ->orderBy('event_time', 'asc')
            ->get()
            ->groupBy('employee_id');

        $presentEmployeesList = Employee::whereIn('id', $todayRecordEmpIds)->get()->map(function ($emp) use ($todayPunches) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_is_late = $punch ? $punch->is_late : false;
            $emp->today_late_minutes = $punch ? $punch->late_minutes : 0;
            return $emp;
        });

        $lateEmpIds = AttendanceRecord::whereDate('event_time', $today)
            ->where('is_late', true)
            ->pluck('employee_id')
            ->unique()
            ->filter();
        $lateEmployeesList = Employee::whereIn('id', $lateEmpIds)->get()->map(function ($emp) use ($todayPunches) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_is_late = true;
            $emp->today_late_minutes = $punch ? $punch->late_minutes : 0;
            return $emp;
        });

        $absentEmployeesList = Employee::where('is_active', true)
            ->whereNotIn('id', $todayRecordEmpIds)
            ->get();

        return view('dashboard', compact(
            'totalEmployees',
            'presentCount',
            'absentCount',
            'lateTodayCount',
            'devices',
            'devicesData',
            'chartData',
            'employeesPaginated',
            'totalEmployeesList',
            'presentEmployeesList',
            'lateEmployeesList',
            'absentEmployeesList'
        ));
    }

    public function sync(Request $request, HikvisionService $hikvisionService)
    {
        $results = $hikvisionService->syncAllDevices();

        $totalNew = array_sum(array_column($results, 'new_events'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'new_events' => $totalNew,
                'synced_at' => now()->format('d/m/Y, H:i:s'),
                'message' => "Sincronización completada. {$totalNew} marcaciones nuevas."
            ]);
        }

        return redirect()->back()->with('success', "Sincronización completada. Se procesaron {$totalNew} marcaciones reales desde ISAPI.");
    }
}
