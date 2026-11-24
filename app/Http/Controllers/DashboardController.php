<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Services\CloudSyncService;
use App\Services\HikvisionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $targetDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $today = $targetDate;

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

        $kpi = $this->buildDailyKpiData($today);

        return view('dashboard', array_merge(
            [
                'targetDate' => $targetDate,
                'devices' => $devices,
                'devicesData' => $devicesData,
                'chartData' => $chartData,
                'employeesPaginated' => $employeesPaginated,
            ],
            $kpi
        ));
    }

    /**
     * Exporta a CSV/Excel el listado de empleados de una tarjeta KPI para una fecha.
     */
    public function exportKpi(Request $request): StreamedResponse
    {
        $targetDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $type = $request->input('type', 'missing_exit');
        $kpi = $this->buildDailyKpiData($targetDate);

        $configs = [
            'missing_exit' => [
                'list' => $kpi['missingExitEmployeesList'],
                'label' => 'Sin Salida',
                'slug' => 'Sin_Salida',
            ],
            'early' => [
                'list' => $kpi['earlyEmployeesList'],
                'label' => 'Anticipados',
                'slug' => 'Anticipados',
            ],
            'present' => [
                'list' => $kpi['presentEmployeesList'],
                'label' => 'Presentes',
                'slug' => 'Presentes',
            ],
            'late' => [
                'list' => $kpi['lateEmployeesList'],
                'label' => 'Tardanzas',
                'slug' => 'Tardanzas',
            ],
            'early_exit' => [
                'list' => $kpi['earlyExitEmployeesList'],
                'label' => 'Salida Anticipada',
                'slug' => 'Salida_Anticipada',
            ],
            'late_exit' => [
                'list' => $kpi['lateExitEmployeesList'],
                'label' => 'Salidas Tarde',
                'slug' => 'Salidas_Tarde',
            ],
            'absent' => [
                'list' => $kpi['absentEmployeesList'],
                'label' => 'Sin Registrar',
                'slug' => 'Sin_Registrar',
            ],
        ];

        $selected = $configs[$type] ?? $configs['missing_exit'];
        $dateStr = $targetDate->format('Y-m-d');
        $fileName = "{$selected['slug']}_{$dateStr}.csv";

        return response()->streamDownload(function () use ($selected, $type, $dateStr) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Fecha',
                'ID Biométrico',
                'Nombre Empleado',
                'Cédula / Documento',
                'Departamento',
                'Cargo',
                'Teléfono',
                'Correo',
                'Categoría',
                'Hora de Entrada',
                'Salida Programada',
                'Hora de Salida',
                'Observación / Estado',
                'Nota de Seguimiento',
            ]);

            foreach ($selected['list'] as $emp) {
                $entryTime = $emp->today_punch_time ?? '--:--';
                $scheduledExit = $emp->today_scheduled_exit ?? 'N/A';
                $exitTime = $emp->today_exit_time ?? '--:--';
                $statusText = $selected['label'];

                if ($type === 'missing_exit') {
                    $exitTime = 'Sin Registro';
                    $statusText = "Entró a {$entryTime} y no registró salida";
                } elseif ($type === 'late') {
                    $statusText = "Tardanza (+{$emp->today_late_minutes} min)";
                } elseif ($type === 'early_exit') {
                    $statusText = "Salida anticipada (-{$emp->today_early_exit_minutes} min)";
                } elseif ($type === 'late_exit') {
                    $statusText = "Salida tarde (+{$emp->today_late_exit_minutes} min)";
                } elseif ($type === 'absent') {
                    $entryTime = 'Sin Registro';
                    $exitTime = 'Sin Registro';
                    $statusText = 'Sin marcación de entrada';
                } elseif ($type === 'present') {
                    $statusText = !empty($emp->today_is_late)
                        ? "Presente (Tardanza +{$emp->today_late_minutes} min)"
                        : 'Presente Puntual';
                }

                fputcsv($handle, [
                    $dateStr,
                    $emp->employee_no,
                    $emp->name,
                    $emp->document_id ?? '',
                    $emp->department?->name ?? 'Sin departamento',
                    $emp->position ?? '',
                    $emp->phone ?? '',
                    $emp->email ?? '',
                    $selected['label'],
                    $entryTime,
                    $scheduledExit,
                    $exitTime,
                    $statusText,
                    $emp->today_note ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Calcula los indicadores KPI diarios y colecciones de empleados para una fecha.
     */
    private function buildDailyKpiData(Carbon $today): array
    {
        $dayOfWeek = $today->dayOfWeekIso;
        $totalEmployeesList = Employee::with('department')->where('is_active', true)->get();
        $totalEmployees = $totalEmployeesList->count();

        $todayPunches = AttendanceRecord::with('device')
            ->whereDate('event_time', $today)
            ->orderBy('event_time', 'asc')
            ->get()
            ->groupBy('employee_id');

        $dailyNotes = EmployeeDailyNote::where('date', $today->toDateString())
            ->get()
            ->keyBy('employee_id');

        $todayRecordEmpIds = $todayPunches->keys()->filter()->values();
        $presentCount = $todayRecordEmpIds->count();
        $absentCount = max(0, $totalEmployees - $presentCount);

        $earlyEmpIdsArray = [];
        $lateEntryEmpIdsArray = [];
        $earlyExitEmpIdsArray = [];
        $lateExitEmpIdsArray = [];
        $missingExitEmpIdsArray = [];

        $lateEntryDetails = [];
        $earlyExitDetails = [];
        $lateExitDetails = [];
        $missingExitDetails = [];

        $now = now();
        $isPastDate = $today->toDateString() < $now->toDateString();
        $isCurrentToday = $today->toDateString() === $now->toDateString();

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
            $lastPunch = $punches->last();

            // 1. Evaluación de Entrada (Puntual / Anticipado / Tardanza de Entrada)
            $isLateEntry = false;
            $lateEntryMinutes = 0;

            if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                $schEntry = Carbon::parse($today->format('Y-m-d') . ' ' . $dayConfig->entry_time);
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

            // 2. Evaluación de Salidas (Anticipada, Tarde o Sin Salida)
            $hasValidExit = $punches->count() >= 2 && $firstPunchTime->diffInMinutes($lastPunch->event_time) >= 30;

            if ($hasValidExit) {
                $exitPunch = $lastPunch;

                if ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time) {
                    $schExit = Carbon::parse($today->format('Y-m-d') . ' ' . $dayConfig->exit_time);
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
            } else {
                // Sin salida: solo considerar cuando ya terminó su turno hoy o en días anteriores
                $shiftEnded = $isPastDate;
                if (!$shiftEnded && $isCurrentToday) {
                    if ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time) {
                        $schExit = Carbon::parse($today->format('Y-m-d') . ' ' . $dayConfig->exit_time);
                        if ($now->gt($schExit->copy()->addMinutes($tolerance))) {
                            $shiftEnded = true;
                        }
                    }
                }

                if ($shiftEnded) {
                    $missingExitEmpIdsArray[] = $emp->id;
                    $missingExitDetails[$emp->id] = [
                        'entry_time' => $firstPunchTime->format('h:i A'),
                        'scheduled_exit' => ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time)
                            ? Carbon::parse($dayConfig->exit_time)->format('h:i A')
                            : 'N/A',
                        'punch_count' => $punches->count(),
                        'schedule_name' => $currentSchedule?->name ?? 'General',
                        'device_name' => $firstPunch->device?->name ?? 'Huellero ISAPI',
                    ];
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
        $earlyEmployeesList = Employee::with('department')->whereIn('id', $earlyEmpIds)->get()->map(function ($emp) use ($todayPunches, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $lateEmpIds = array_unique($lateEntryEmpIdsArray);
        $lateTodayCount = count($lateEmpIds);
        $lateEmployeesList = Employee::with('department')->whereIn('id', $lateEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateEntryDetails, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $lateEntryDetails[$emp->id]['entry_time'] ?? ($punch ? $punch->event_time->format('h:i A') : '--:--');
            $emp->today_is_late = true;
            $emp->today_late_minutes = $lateEntryDetails[$emp->id]['minutes'] ?? ($punch ? abs((int)$punch->late_minutes) : 0);
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $earlyExitEmpIds = array_unique($earlyExitEmpIdsArray);
        $earlyExitTodayCount = count($earlyExitEmpIds);
        $earlyExitEmployeesList = Employee::with('department')->whereIn('id', $earlyExitEmpIds)->get()->map(function ($emp) use ($todayPunches, $earlyExitDetails, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_exit_time = $earlyExitDetails[$emp->id]['exit_time'] ?? '--:--';
            $emp->today_early_exit_minutes = $earlyExitDetails[$emp->id]['minutes'] ?? 0;
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $lateExitEmpIds = array_unique($lateExitEmpIdsArray);
        $lateExitTodayCount = count($lateExitEmpIds);
        $lateExitEmployeesList = Employee::with('department')->whereIn('id', $lateExitEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateExitDetails, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_exit_time = $lateExitDetails[$emp->id]['exit_time'] ?? '--:--';
            $emp->today_late_exit_minutes = $lateExitDetails[$emp->id]['minutes'] ?? 0;
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $missingExitEmpIds = array_unique($missingExitEmpIdsArray);
        $missingExitCount = count($missingExitEmpIds);
        $missingExitEmployeesList = Employee::with('department')->whereIn('id', $missingExitEmpIds)->get()->map(function ($emp) use ($todayPunches, $missingExitDetails, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $details = $missingExitDetails[$emp->id] ?? [];
            $emp->today_punch_time = $details['entry_time'] ?? ($punch ? $punch->event_time->format('h:i A') : '--:--');
            $emp->today_scheduled_exit = $details['scheduled_exit'] ?? 'N/A';
            $emp->today_punch_count = $details['punch_count'] ?? 1;
            $emp->today_schedule_name = $details['schedule_name'] ?? 'General';
            $emp->today_device_name = $details['device_name'] ?? 'Huellero ISAPI';
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $presentEmployeesList = Employee::with('department')->whereIn('id', $todayRecordEmpIds)->get()->map(function ($emp) use ($todayPunches, $lateEntryEmpIdsArray, $lateEntryDetails, $dailyNotes) {
            $punch = $todayPunches->get($emp->id)?->first();
            $isLate = in_array($emp->id, $lateEntryEmpIdsArray);
            $emp->today_punch_time = $punch ? $punch->event_time->format('h:i A') : '--:--';
            $emp->today_is_late = $isLate;
            $emp->today_late_minutes = $isLate ? ($lateEntryDetails[$emp->id]['minutes'] ?? ($punch ? abs((int)$punch->late_minutes) : 0)) : 0;
            $emp->today_note = $dailyNotes->get($emp->id)?->note;
            return $emp;
        });

        $absentEmployeesList = Employee::with('department')->where('is_active', true)
            ->whereNotIn('id', $todayRecordEmpIds)
            ->get()
            ->map(function ($emp) use ($dailyNotes) {
                $emp->today_note = $dailyNotes->get($emp->id)?->note;
                return $emp;
            });

        return compact(
            'totalEmployees',
            'presentCount',
            'absentCount',
            'lateTodayCount',
            'earlyExitTodayCount',
            'lateExitTodayCount',
            'missingExitCount',
            'earlyTodayCount',
            'totalEmployeesList',
            'earlyEmployeesList',
            'presentEmployeesList',
            'lateEmployeesList',
            'earlyExitEmployeesList',
            'lateExitEmployeesList',
            'missingExitEmployeesList',
            'absentEmployeesList'
        );
    }

    public function sync(Request $request, HikvisionService $hikvisionService, CloudSyncService $cloudSyncService)
    {
        $results = $hikvisionService->syncAllDevices();

        $totalNew = array_sum(array_column($results, 'new_events'));

        $cloudResult = null;
        $isDesktop = config('nativephp-internal.running') || env('NATIVEPHP_RUNNING', false);
        if ($isDesktop) {
            try {
                // Enviar inmediatamente las marcaciones recolectadas a la nube
                $cloudResult = $cloudSyncService->push();
            } catch (\Throwable $e) {
                Log::warning('Error sincronizando marcaciones con la nube tras ISAPI: ' . $e->getMessage());
            }
        }

        $cloudText = '';
        if ($cloudResult) {
            $cloudText = !empty($cloudResult['success']) 
                ? ' y sincronizadas con la nube (access.intalnet.com).' 
                : ' (Marcaciones guardadas localmente. La nube no respondió).';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'new_events' => $totalNew,
                'cloud_synced' => (bool) ($cloudResult['success'] ?? false),
                'synced_at' => now()->format('d/m/Y, H:i:s'),
                'message' => "Sincronización completada. {$totalNew} marcaciones nuevas{$cloudText}"
            ]);
        }

        return redirect()->back()->with('success', "Sincronización completada. Se procesaron {$totalNew} marcaciones reales desde ISAPI{$cloudText}");
    }

    /**
     * Sincronización manual bidireccional con la nube central (access.intalnet.com).
     */
    public function cloudSync(Request $request, CloudSyncService $cloudSyncService)
    {
        $result = $cloudSyncService->sync();

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if (!empty($result['success'])) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
