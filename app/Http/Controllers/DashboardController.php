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
    public function index(Request $request)
    {
        $targetDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $today = $targetDate;

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

        // Lista de empleados paginada con su estado de hoy (filtrable por búsqueda)
        $dayOfWeek = $today->dayOfWeekIso;

        $empQuery = Employee::with('department')->where('is_active', true);
        if ($request->filled('search')) {
            $empQuery->where('name', 'like', '%' . $request->search . '%')
                     ->orWhere('employee_no', 'like', '%' . $request->search . '%');
        }
        $employeesPaginated = $empQuery->paginate(10)->withQueryString();

        $paginatedIds = $employeesPaginated->pluck('id');
        $todayPunchesPaginated = AttendanceRecord::whereDate('event_time', $today)
            ->whereIn('employee_id', $paginatedIds)
            ->orderBy('event_time', 'asc')
            ->get()
            ->groupBy('employee_id');

        $employeesPaginated->getCollection()->transform(function ($emp) use ($todayPunchesPaginated, $targetDate, $dayOfWeek) {
            $punches = $todayPunchesPaginated->get($emp->id);
            $punch = $punches?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : null;

            $isLate = false;
            $lateMinutes = 0;
            if ($punch) {
                if ($punch->is_late) {
                    $isLate = true;
                    $lateMinutes = abs((int) $punch->late_minutes);
                } else {
                    $currentSchedule = $emp->currentSchedule();
                    if ($currentSchedule) {
                        $dayConfig = $currentSchedule->days->where('day_of_week', $dayOfWeek)->first();
                        if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                            $schEntry = \Carbon\Carbon::parse($targetDate->format('Y-m-d') . ' ' . $dayConfig->entry_time);
                            $tolerance = $currentSchedule->tolerance_minutes ?? 5;
                            $entryLimit = $schEntry->copy()->addMinutes($tolerance);
                            if ($punch->event_time->gt($entryLimit)) {
                                $isLate = true;
                                $lateMinutes = abs((int) $punch->event_time->diffInMinutes($schEntry));
                            }
                        }
                    }
                }
            }
            $emp->today_is_late = $isLate;
            $emp->today_late_minutes = $lateMinutes;
            return $emp;
        });

        // Colecciones de empleados detalladas para interactividad en el Dashboard
        $totalEmployeesList = Employee::with('department')->where('is_active', true)->get();

        $todayPunches = AttendanceRecord::whereDate('event_time', $today)
            ->orderBy('event_time', 'asc')
            ->get()
            ->groupBy('employee_id');

        $earlyEmpIdsArray = [];
        $lateEntryEmpIdsArray = [];
        $earlyExitEmpIdsArray = [];
        $lateExitEmpIdsArray = [];
        $lateEntryDetails = [];
        $earlyExitDetails = [];
        $lateExitDetails = [];

        foreach ($totalEmployeesList as $emp) {
            $punches = $todayPunches->get($emp->id);
            if (!$punches || $punches->isEmpty()) {
                continue;
            }

            $currentSchedule = $emp->currentSchedule();
            $dayConfig = $currentSchedule ? $currentSchedule->days->where('day_of_week', $dayOfWeek)->first() : null;
            $tolerance = $currentSchedule ? ($currentSchedule->tolerance_minutes ?? 5) : 5;

            $firstPunch = $punches->first();
            $firstPunchTime = clone $firstPunch->event_time;

            // 1. Evaluación de Entrada (Puntual / Anticipado / Tardanza de Entrada)
            $isLateEntry = false;
            $lateEntryMinutes = 0;

            if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                $schEntry = \Carbon\Carbon::parse($today->format('Y-m-d') . ' ' . $dayConfig->entry_time);
                $earlyLimit = $schEntry->copy()->subMinutes($tolerance);
                $lateLimit = $schEntry->copy()->addMinutes($tolerance);

                if ($firstPunchTime->lt($earlyLimit)) {
                    $earlyEmpIdsArray[] = $emp->id;
                } elseif ($firstPunchTime->gt($lateLimit) || $firstPunch->is_late) {
                    $isLateEntry = true;
                    $lateEntryMinutes = abs((int) $firstPunchTime->diffInMinutes($schEntry));
                }
            } elseif ($firstPunch->is_late) {
                $isLateEntry = true;
                $lateEntryMinutes = abs((int) ($firstPunch->late_minutes ?? 0));
            }

            if ($isLateEntry) {
                $lateEntryEmpIdsArray[] = $emp->id;
                $lateEntryDetails[$emp->id] = [
                    'entry_time' => $firstPunchTime->format('h:i A'),
                    'minutes' => $lateEntryMinutes,
                ];
            }

            // 2. Evaluación de Salidas (Anticipada y Tarde)
            if ($punches->count() >= 2) {
                $lastPunch = $punches->last();
                // Debe haber al menos 30 minutos entre la 1ra y última marcación para considerarse salida válida
                if ($firstPunchTime->diffInMinutes($lastPunch->event_time) >= 30) {
                    $exitPunch = $lastPunch;

                    if ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time) {
                        $schExit = \Carbon\Carbon::parse($today->format('Y-m-d') . ' ' . $dayConfig->exit_time);
                        $lateExitLimit = $schExit->copy()->addMinutes($tolerance);

                        // A. Salida Anticipada (marcó salida antes de su horario de salida programado)
                        if ($exitPunch->event_time->lt($schExit)) {
                            $earlyExitEmpIdsArray[] = $emp->id;
                            $earlyExitMinutes = abs((int) $schExit->diffInMinutes($exitPunch->event_time));
                            $earlyExitDetails[$emp->id] = [
                                'exit_time' => $exitPunch->event_time->format('h:i A'),
                                'minutes' => $earlyExitMinutes,
                            ];
                        }
                        // B. Salida Tarde (marcó salida después de su horario más la tolerancia)
                        elseif ($exitPunch->event_time->gt($lateExitLimit)) {
                            $lateExitEmpIdsArray[] = $emp->id;
                            $lateExitMinutes = abs((int) $exitPunch->event_time->diffInMinutes($schExit));
                            $lateExitDetails[$emp->id] = [
                                'exit_time' => $exitPunch->event_time->format('h:i A'),
                                'minutes' => $lateExitMinutes,
                            ];
                        }
                    }
                }
            }
        }

        // Asegurar que registros de BD con is_late se incluyan en tardanzas de entrada
        $dbLateEmpIds = AttendanceRecord::whereDate('event_time', $today)
            ->where('is_late', true)
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        foreach ($dbLateEmpIds as $dbLateId) {
            if (!in_array($dbLateId, $lateEntryEmpIdsArray)) {
                $lateEntryEmpIdsArray[] = $dbLateId;
            }
        }

        // Formatear listas y contadores de tarjetas
        $earlyEmpIds = array_unique($earlyEmpIdsArray);
        $earlyTodayCount = count($earlyEmpIds);
        $earlyEmployeesList = Employee::with('department')->whereIn('id', $earlyEmpIds)->get()->map(function ($emp) use ($todayPunches) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            return $emp;
        });

        $lateEmpIds = array_unique($lateEntryEmpIdsArray);
        $lateTodayCount = count($lateEmpIds);
        $lateEmployeesList = Employee::with('department')->whereIn('id', $lateEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateEntryDetails) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $lateEntryDetails[$emp->id]['entry_time'] ?? ($punch ? $punch->event_time->format('h:i A') : '--:--');
            $emp->today_is_late = true;
            $emp->today_late_minutes = $lateEntryDetails[$emp->id]['minutes'] ?? ($punch ? abs((int)$punch->late_minutes) : 0);
            return $emp;
        });

        $earlyExitEmpIds = array_unique($earlyExitEmpIdsArray);
        $earlyExitTodayCount = count($earlyExitEmpIds);
        $earlyExitEmployeesList = Employee::with('department')->whereIn('id', $earlyExitEmpIds)->get()->map(function ($emp) use ($todayPunches, $earlyExitDetails) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_exit_time = $earlyExitDetails[$emp->id]['exit_time'] ?? '--:--';
            $emp->today_early_exit_minutes = $earlyExitDetails[$emp->id]['minutes'] ?? 0;
            return $emp;
        });

        $lateExitEmpIds = array_unique($lateExitEmpIdsArray);
        $lateExitTodayCount = count($lateExitEmpIds);
        $lateExitEmployeesList = Employee::with('department')->whereIn('id', $lateExitEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateExitDetails) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_exit_time = $lateExitDetails[$emp->id]['exit_time'] ?? '--:--';
            $emp->today_late_exit_minutes = $lateExitDetails[$emp->id]['minutes'] ?? 0;
            return $emp;
        });

        $presentEmployeesList = Employee::with('department')->whereIn('id', $todayRecordEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateEntryEmpIdsArray, $lateEntryDetails) {
            $punch = $todayPunches->get($emp->id)?->first();
            $isLate = in_array($emp->id, $lateEntryEmpIdsArray);
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_is_late = $isLate;
            $emp->today_late_minutes = $isLate ? ($lateEntryDetails[$emp->id]['minutes'] ?? ($punch ? abs((int)$punch->late_minutes) : 0)) : 0;
            return $emp;
        });

        $absentEmployeesList = Employee::with('department')->where('is_active', true)
            ->whereNotIn('id', $todayRecordEmpIds)
            ->get();

        return view('dashboard', compact(
            'targetDate',
            'totalEmployees',
            'presentCount',
            'absentCount',
            'lateTodayCount',
            'earlyExitTodayCount',
            'lateExitTodayCount',
            'earlyTodayCount',
            'devices',
            'devicesData',
            'chartData',
            'employeesPaginated',
            'totalEmployeesList',
            'earlyEmployeesList',
            'presentEmployeesList',
            'lateEmployeesList',
            'earlyExitEmployeesList',
            'lateExitEmployeesList',
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
