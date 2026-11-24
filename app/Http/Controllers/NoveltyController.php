<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoveltyController extends Controller
{
    /**
     * Muestra la vista de Novedades con el consolidado del mes,
     * gráficos diarios y el desglose de llegadas tarde y salidas tempranas.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        
        // Validación básica de formato YYYY-MM
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $carbonMonth = Carbon::parse($selectedMonth . '-01');
        $startDate = $carbonMonth->copy()->startOfMonth();
        $endDate = $carbonMonth->copy()->endOfMonth();
        $daysInMonth = $carbonMonth->daysInMonth;

        $prevMonth = $carbonMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $carbonMonth->copy()->addMonth()->format('Y-m');

        // Meses disponibles con registros para el selector
        $availableMonths = AttendanceRecord::selectRaw("strftime('%Y-%m', event_time) as ym")
            ->whereNotNull('event_time')
            ->distinct()
            ->orderBy('ym', 'desc')
            ->pluck('ym')
            ->toArray();

        // Asegurar que el mes seleccionado y el mes actual estén en la lista
        $currentYm = now()->format('Y-m');
        if (!in_array($currentYm, $availableMonths)) {
            array_unshift($availableMonths, $currentYm);
        }
        if (!in_array($selectedMonth, $availableMonths)) {
            $availableMonths[] = $selectedMonth;
            rsort($availableMonths);
        }

        $availableMonthsData = array_map(function ($ym) {
            $c = Carbon::parse($ym . '-01');
            return [
                'value' => $ym,
                'label' => ucfirst($c->isoFormat('MMMM YYYY')),
            ];
        }, $availableMonths);

        // Pre-cargar todos los empleados con sus departamentos para evitar N+1
        $allEmployees = Employee::with('department')->orderBy('name')->get()->keyBy('id');
        $departments = Department::orderBy('name')->get();

        // Obtener todas las marcaciones del mes
        $records = AttendanceRecord::with(['device'])
            ->whereBetween('event_time', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->orderBy('event_time', 'asc')
            ->get();

        // Agrupar marcaciones por fecha (Y-m-d) y por empleado_id
        $recordsByDateAndEmp = [];
        foreach ($records as $record) {
            if (!$record->employee_id) {
                continue;
            }
            $dateKey = $record->event_time->format('Y-m-d');
            $recordsByDateAndEmp[$dateKey][$record->employee_id][] = $record;
        }

        // Estructuras de datos para el consolidado
        $dailyStats = [];
        $allNovelties = [];
        $allLateEvents = [];
        $allEarlyExitEvents = [];

        $totalLateMinutes = 0;
        $totalEarlyExitMinutes = 0;

        $lateCountByEmployee = [];
        $earlyCountByEmployee = [];

        $todayStr = now()->toDateString();
        $now = now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $startDate->copy()->day($day);
            $dStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

            $dayLateItems = [];
            $dayEarlyItems = [];

            if (isset($recordsByDateAndEmp[$dStr])) {
                foreach ($recordsByDateAndEmp[$dStr] as $empId => $punches) {
                    $employee = $allEmployees->get($empId);
                    if (!$employee) {
                        continue;
                    }

                    $schedule = $employee->currentSchedule();
                    $dayConfig = $schedule ? $schedule->days->where('day_of_week', $dayOfWeek)->first() : null;
                    $tolerance = $schedule ? ($schedule->tolerance_minutes ?? 5) : 5;

                    $firstPunch = $punches[0];
                    $lastPunch = end($punches);

                    // 1. EVALUAR LLEGADA TARDE
                    $isLate = false;
                    $lateMinutes = 0;
                    $expectedEntryStr = null;

                    if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                        $schEntry = Carbon::parse($dStr . ' ' . $dayConfig->entry_time);
                        $expectedEntryStr = $schEntry->format('h:i A');
                        $lateLimit = $schEntry->copy()->addMinutes($tolerance);

                        if ($firstPunch->event_time->gt($lateLimit) || $firstPunch->is_late) {
                            $isLate = true;
                            $lateMinutes = abs((int) $firstPunch->event_time->diffInMinutes($schEntry));
                        }
                    } elseif ($firstPunch->is_late) {
                        $isLate = true;
                        $lateMinutes = abs((int) $firstPunch->late_minutes);
                    }

                    if ($isLate) {
                        $lateItem = [
                            'id' => 'late_' . $dStr . '_' . $employee->id,
                            'date' => $dStr,
                            'day_number' => $day,
                            'day_name' => ucfirst($currentDate->isoFormat('dddd')),
                            'day_short' => ucfirst($currentDate->isoFormat('ddd')),
                            'formatted_date' => $currentDate->isoFormat('D [de] MMMM, YYYY'),
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->display_name,
                            'employee_no' => $employee->employee_no,
                            'document_id' => $employee->document_id ?? 'N/A',
                            'department_id' => $employee->department_id,
                            'department_name' => $employee->department?->name ?? 'Sin Departamento',
                            'position' => $employee->position ?? 'Colaborador',
                            'type' => 'late',
                            'type_label' => 'Llegada Tarde',
                            'scheduled_time' => $expectedEntryStr ?? '08:00 AM',
                            'actual_time' => $firstPunch->event_time->format('h:i A'),
                            'actual_timestamp' => $firstPunch->event_time->format('H:i:s'),
                            'minutes_diff' => $lateMinutes,
                            'formatted_diff' => self::formatMinutesHuman($lateMinutes) . ' tarde',
                            'tolerance_minutes' => $tolerance,
                            'schedule_name' => $schedule?->name ?? 'Horario General',
                            'device_name' => $firstPunch->device?->name ?? 'Huellero ISAPI',
                        ];

                        $dayLateItems[] = $lateItem;
                        $allLateEvents[] = $lateItem;
                        $allNovelties[] = $lateItem;
                        $totalLateMinutes += $lateMinutes;

                        if (!isset($lateCountByEmployee[$employee->id])) {
                            $lateCountByEmployee[$employee->id] = [
                                'employee' => $employee,
                                'count' => 0,
                                'total_minutes' => 0,
                            ];
                        }
                        $lateCountByEmployee[$employee->id]['count']++;
                        $lateCountByEmployee[$employee->id]['total_minutes'] += $lateMinutes;
                    }

                    // 2. EVALUAR SALIDA TEMPRANA (Se fue antes del horario)
                    // Condición para no marcar salida temprana si es hoy y el turno aún no ha finalizado
                    $canEvaluateEarlyExit = true;
                    if ($dStr === $todayStr) {
                        if ($dayConfig && $dayConfig->exit_time) {
                            $schExitToday = Carbon::parse($dStr . ' ' . $dayConfig->exit_time);
                            // Si todavía no llega la hora de salida de hoy y el último punch no es explícitamente check_out
                            if ($now->lt($schExitToday) && ($lastPunch->attendance_type !== 'check_out')) {
                                $canEvaluateEarlyExit = false;
                            }
                        }
                    }

                    if ($canEvaluateEarlyExit && count($punches) >= 2) {
                        // Al menos 25 minutos entre primera y última marcación para evitar dobles toques al entrar
                        if ($firstPunch->event_time->diffInMinutes($lastPunch->event_time) >= 25) {
                            if ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time) {
                                $schExit = Carbon::parse($dStr . ' ' . $dayConfig->exit_time);
                                $earlyLimit = $schExit->copy()->subMinutes($tolerance);

                                if ($lastPunch->event_time->lt($earlyLimit)) {
                                    $earlyMins = abs((int) $schExit->diffInMinutes($lastPunch->event_time));
                                    
                                    if ($earlyMins > $tolerance) {
                                        $earlyItem = [
                                            'id' => 'early_' . $dStr . '_' . $employee->id,
                                            'date' => $dStr,
                                            'day_number' => $day,
                                            'day_name' => ucfirst($currentDate->isoFormat('dddd')),
                                            'day_short' => ucfirst($currentDate->isoFormat('ddd')),
                                            'formatted_date' => $currentDate->isoFormat('D [de] MMMM, YYYY'),
                                            'employee_id' => $employee->id,
                                            'employee_name' => $employee->display_name,
                                            'employee_no' => $employee->employee_no,
                                            'document_id' => $employee->document_id ?? 'N/A',
                                            'department_id' => $employee->department_id,
                                            'department_name' => $employee->department?->name ?? 'Sin Departamento',
                                            'position' => $employee->position ?? 'Colaborador',
                                            'type' => 'early_exit',
                                            'type_label' => 'Salida Temprana',
                                            'scheduled_time' => Carbon::parse($dayConfig->exit_time)->format('h:i A'),
                                            'actual_time' => $lastPunch->event_time->format('h:i A'),
                                            'actual_timestamp' => $lastPunch->event_time->format('H:i:s'),
                                            'minutes_diff' => $earlyMins,
                                            'formatted_diff' => self::formatMinutesHuman($earlyMins) . ' antes',
                                            'tolerance_minutes' => $tolerance,
                                            'schedule_name' => $schedule?->name ?? 'Horario General',
                                            'device_name' => $lastPunch->device?->name ?? 'Huellero ISAPI',
                                        ];

                                        $dayEarlyItems[] = $earlyItem;
                                        $allEarlyExitEvents[] = $earlyItem;
                                        $allNovelties[] = $earlyItem;
                                        $totalEarlyExitMinutes += $earlyMins;

                                        if (!isset($earlyCountByEmployee[$employee->id])) {
                                            $earlyCountByEmployee[$employee->id] = [
                                                'employee' => $employee,
                                                'count' => 0,
                                                'total_minutes' => 0,
                                            ];
                                        }
                                        $earlyCountByEmployee[$employee->id]['count']++;
                                        $earlyCountByEmployee[$employee->id]['total_minutes'] += $earlyMins;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $dailyStats[$day] = [
                'day' => $day,
                'date' => $dStr,
                'day_name' => ucfirst($currentDate->isoFormat('dddd')),
                'day_short' => ucfirst($currentDate->isoFormat('ddd')),
                'is_weekend' => $currentDate->isWeekend(),
                'is_today' => ($dStr === $todayStr),
                'late_count' => count($dayLateItems),
                'early_count' => count($dayEarlyItems),
                'total_count' => count($dayLateItems) + count($dayEarlyItems),
                'late_items' => $dayLateItems,
                'early_items' => $dayEarlyItems,
                'all_items' => array_merge($dayLateItems, $dayEarlyItems),
            ];
        }

        // Ordenar rankings de empleados
        uasort($lateCountByEmployee, fn($a, $b) => $b['count'] <=> $a['count'] ?: $b['total_minutes'] <=> $a['total_minutes']);
        uasort($earlyCountByEmployee, fn($a, $b) => $b['count'] <=> $a['count'] ?: $b['total_minutes'] <=> $a['total_minutes']);

        $topLateEmployees = array_slice($lateCountByEmployee, 0, 5, true);
        $topEarlyEmployees = array_slice($earlyCountByEmployee, 0, 5, true);

        // Día con más novedades del mes
        $worstDay = null;
        $maxNoveltiesDay = 0;
        foreach ($dailyStats as $stat) {
            if ($stat['total_count'] > $maxNoveltiesDay) {
                $maxNoveltiesDay = $stat['total_count'];
                $worstDay = $stat;
            }
        }

        // Preparar datos para el gráfico Chart.js
        $chartLabels = [];
        $chartLateData = [];
        $chartEarlyData = [];
        $chartTooltipsData = [];

        foreach ($dailyStats as $dayNumber => $stat) {
            $chartLabels[] = 'Día ' . $dayNumber;
            $chartLateData[] = $stat['late_count'];
            $chartEarlyData[] = $stat['early_count'];

            // Tooltip preview con nombres de empleados
            $lateNames = array_map(fn($item) => $item['employee_name'] . ' (' . $item['actual_time'] . ')', $stat['late_items']);
            $earlyNames = array_map(fn($item) => $item['employee_name'] . ' (' . $item['actual_time'] . ')', $stat['early_items']);

            $chartTooltipsData[] = [
                'day' => $dayNumber,
                'date' => $stat['date'],
                'day_name' => $stat['day_name'],
                'late_names' => $lateNames,
                'early_names' => $earlyNames,
                'total' => $stat['total_count'],
            ];
        }

        // Filtrado dinámico de la lista de novedades según la petición GET
        $filteredNovelties = collect($allNovelties);

        // Filtro por tipo (late, early_exit)
        $filterType = $request->input('type', 'all');
        if ($filterType === 'late') {
            $filteredNovelties = $filteredNovelties->where('type', 'late');
        } elseif ($filterType === 'early_exit') {
            $filteredNovelties = $filteredNovelties->where('type', 'early_exit');
        }

        // Filtro por día específico (ej. day=15 o day=2026-10-15)
        $filterDay = $request->input('day');
        if (!empty($filterDay)) {
            if (is_numeric($filterDay)) {
                $filterDayInt = (int) $filterDay;
                $filteredNovelties = $filteredNovelties->where('day_number', $filterDayInt);
            } else {
                $filteredNovelties = $filteredNovelties->where('date', $filterDay);
            }
        }

        // Filtro por departamento
        $filterDept = $request->input('department_id');
        if (!empty($filterDept)) {
            $filteredNovelties = $filteredNovelties->where('department_id', (int) $filterDept);
        }

        // Filtro por búsqueda de empleado
        $searchQuery = trim($request->input('search', ''));
        if (!empty($searchQuery)) {
            $qLower = mb_strtolower($searchQuery);
            $filteredNovelties = $filteredNovelties->filter(function ($item) use ($qLower) {
                return str_contains(mb_strtolower($item['employee_name']), $qLower)
                    || str_contains(mb_strtolower((string)$item['employee_no']), $qLower)
                    || str_contains(mb_strtolower((string)$item['document_id']), $qLower);
            });
        }

        // Ordenar la lista filtrada por fecha descendente y hora
        $filteredNovelties = $filteredNovelties->sortByDesc(function ($item) {
            return $item['date'] . ' ' . $item['actual_timestamp'];
        })->values();

        // Resumen de KPIs
        $totalLateCount = count($allLateEvents);
        $totalEarlyCount = count($allEarlyExitEvents);
        $totalNoveltiesCount = count($allNovelties);

        $avgLateMinutes = $totalLateCount > 0 ? round($totalLateMinutes / $totalLateCount) : 0;
        $avgEarlyMinutes = $totalEarlyCount > 0 ? round($totalEarlyExitMinutes / $totalEarlyCount) : 0;

        $uniqueEmployeesWithNovelties = collect($allNovelties)->pluck('employee_id')->unique()->count();
        $totalEmployeesCount = $allEmployees->count();

        return view('novedades.index', [
            'selectedMonth' => $selectedMonth,
            'carbonMonth' => $carbonMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'availableMonths' => $availableMonthsData,
            'departments' => $departments,
            'dailyStats' => $dailyStats,
            'allNovelties' => $allNovelties,
            'filteredNovelties' => $filteredNovelties,
            'filterType' => $filterType,
            'filterDay' => $filterDay,
            'filterDept' => $filterDept,
            'searchQuery' => $searchQuery,
            'totalLateCount' => $totalLateCount,
            'totalLateMinutes' => $totalLateMinutes,
            'avgLateMinutes' => $avgLateMinutes,
            'totalEarlyCount' => $totalEarlyCount,
            'totalEarlyExitMinutes' => $totalEarlyExitMinutes,
            'avgEarlyMinutes' => $avgEarlyMinutes,
            'totalNoveltiesCount' => $totalNoveltiesCount,
            'uniqueEmployeesWithNovelties' => $uniqueEmployeesWithNovelties,
            'totalEmployeesCount' => $totalEmployeesCount,
            'worstDay' => $worstDay,
            'topLateEmployees' => $topLateEmployees,
            'topEarlyEmployees' => $topEarlyEmployees,
            'chartLabels' => $chartLabels,
            'chartLateData' => $chartLateData,
            'chartEarlyData' => $chartEarlyData,
            'chartTooltipsData' => $chartTooltipsData,
        ]);
    }

    /**
     * Exporta el reporte de novedades del mes a un archivo CSV estructurado.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $carbonMonth = Carbon::parse($selectedMonth . '-01');
        $startDate = $carbonMonth->copy()->startOfMonth();
        $endDate = $carbonMonth->copy()->endOfMonth();
        $daysInMonth = $carbonMonth->daysInMonth;

        $allEmployees = Employee::with('department')->get()->keyBy('id');

        $records = AttendanceRecord::with(['device'])
            ->whereBetween('event_time', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59')
            ])
            ->orderBy('event_time', 'asc')
            ->get();

        $recordsByDateAndEmp = [];
        foreach ($records as $record) {
            if (!$record->employee_id) continue;
            $dateKey = $record->event_time->format('Y-m-d');
            $recordsByDateAndEmp[$dateKey][$record->employee_id][] = $record;
        }

        $allNovelties = [];
        $todayStr = now()->toDateString();
        $now = now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $startDate->copy()->day($day);
            $dStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeekIso;

            if (isset($recordsByDateAndEmp[$dStr])) {
                foreach ($recordsByDateAndEmp[$dStr] as $empId => $punches) {
                    $employee = $allEmployees->get($empId);
                    if (!$employee) continue;

                    $schedule = $employee->currentSchedule();
                    $dayConfig = $schedule ? $schedule->days->where('day_of_week', $dayOfWeek)->first() : null;
                    $tolerance = $schedule ? ($schedule->tolerance_minutes ?? 5) : 5;

                    $firstPunch = $punches[0];
                    $lastPunch = end($punches);

                    // Llegada tarde
                    $isLate = false;
                    $lateMinutes = 0;
                    $expectedEntryStr = null;

                    if ($dayConfig && $dayConfig->is_working_day && $dayConfig->entry_time) {
                        $schEntry = Carbon::parse($dStr . ' ' . $dayConfig->entry_time);
                        $expectedEntryStr = $schEntry->format('H:i');
                        $lateLimit = $schEntry->copy()->addMinutes($tolerance);

                        if ($firstPunch->event_time->gt($lateLimit) || $firstPunch->is_late) {
                            $isLate = true;
                            $lateMinutes = abs((int) $firstPunch->event_time->diffInMinutes($schEntry));
                        }
                    } elseif ($firstPunch->is_late) {
                        $isLate = true;
                        $lateMinutes = abs((int) $firstPunch->late_minutes);
                    }

                    if ($isLate) {
                        $allNovelties[] = [
                            'fecha' => $dStr,
                            'dia' => ucfirst($currentDate->isoFormat('dddd')),
                            'empleado_id' => $employee->employee_no,
                            'nombre' => $employee->name,
                            'cedula' => $employee->document_id ?? '',
                            'departamento' => $employee->department?->name ?? 'Sin Departamento',
                            'cargo' => $employee->position ?? '',
                            'tipo' => 'Llegada Tarde',
                            'horario_programado' => $expectedEntryStr ?? '08:00',
                            'marcacion_real' => $firstPunch->event_time->format('H:i:s'),
                            'diferencia_minutos' => $lateMinutes,
                            'diferencia_texto' => self::formatMinutesHuman($lateMinutes) . ' tarde',
                            'huellero' => $firstPunch->device?->name ?? 'Huellero ISAPI',
                        ];
                    }

                    // Salida temprana
                    $canEvaluateEarlyExit = true;
                    if ($dStr === $todayStr) {
                        if ($dayConfig && $dayConfig->exit_time) {
                            $schExitToday = Carbon::parse($dStr . ' ' . $dayConfig->exit_time);
                            if ($now->lt($schExitToday) && ($lastPunch->attendance_type !== 'check_out')) {
                                $canEvaluateEarlyExit = false;
                            }
                        }
                    }

                    if ($canEvaluateEarlyExit && count($punches) >= 2) {
                        if ($firstPunch->event_time->diffInMinutes($lastPunch->event_time) >= 25) {
                            if ($dayConfig && $dayConfig->is_working_day && $dayConfig->exit_time) {
                                $schExit = Carbon::parse($dStr . ' ' . $dayConfig->exit_time);
                                $earlyLimit = $schExit->copy()->subMinutes($tolerance);

                                if ($lastPunch->event_time->lt($earlyLimit)) {
                                    $earlyMins = abs((int) $schExit->diffInMinutes($lastPunch->event_time));
                                    if ($earlyMins > $tolerance) {
                                        $allNovelties[] = [
                                            'fecha' => $dStr,
                                            'dia' => ucfirst($currentDate->isoFormat('dddd')),
                                            'empleado_id' => $employee->employee_no,
                                            'nombre' => $employee->name,
                                            'cedula' => $employee->document_id ?? '',
                                            'departamento' => $employee->department?->name ?? 'Sin Departamento',
                                            'cargo' => $employee->position ?? '',
                                            'tipo' => 'Salida Temprana',
                                            'horario_programado' => Carbon::parse($dayConfig->exit_time)->format('H:i'),
                                            'marcacion_real' => $lastPunch->event_time->format('H:i:s'),
                                            'diferencia_minutos' => $earlyMins,
                                            'diferencia_texto' => self::formatMinutesHuman($earlyMins) . ' antes',
                                            'huellero' => $lastPunch->device?->name ?? 'Huellero ISAPI',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Filtros opcionales en exportación
        $filterType = $request->input('type');
        if ($filterType === 'late') {
            $allNovelties = array_filter($allNovelties, fn($n) => $n['tipo'] === 'Llegada Tarde');
        } elseif ($filterType === 'early_exit') {
            $allNovelties = array_filter($allNovelties, fn($n) => $n['tipo'] === 'Salida Temprana');
        }

        $filterDept = $request->input('department_id');
        if (!empty($filterDept)) {
            $deptObj = Department::find($filterDept);
            if ($deptObj) {
                $allNovelties = array_filter($allNovelties, fn($n) => $n['departamento'] === $deptObj->name);
            }
        }

        $fileName = 'Novedades_Asistencia_' . $selectedMonth . '.csv';

        return response()->streamDownload(function () use ($allNovelties) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidad total con Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($handle, [
                'Fecha',
                'Día',
                'ID Empleado',
                'Nombre y Apellidos',
                'Cédula / Documento',
                'Departamento',
                'Cargo',
                'Tipo de Novedad',
                'Horario Programado',
                'Hora Marcación Real',
                'Diferencia (Minutos)',
                'Impacto / Tiempo',
                'Terminal ISAPI'
            ]);

            foreach ($allNovelties as $row) {
                fputcsv($handle, [
                    $row['fecha'],
                    $row['dia'],
                    $row['empleado_id'],
                    $row['nombre'],
                    $row['cedula'],
                    $row['departamento'],
                    $row['cargo'],
                    $row['tipo'],
                    $row['horario_programado'],
                    $row['marcacion_real'],
                    $row['diferencia_minutos'],
                    $row['diferencia_texto'],
                    $row['huellero'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Helper para formatear minutos a texto legible (ej: 45 min, 2 h, 2 h 15 min)
     */
    public static function formatMinutesHuman(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = floor($minutes / 60);
        $rem = $minutes % 60;
        return $rem > 0 ? "{$hours} h {$rem} min" : "{$hours} h";
    }
}
