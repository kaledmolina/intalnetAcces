<?php

namespace App\Mcp\Support;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultas de solo lectura sobre datos de asistencia.
 *
 * La tardanza NO se recalcula aquí: se reutiliza el resultado persistido por
 * App\Services\HikvisionService en attendance_records.is_late / late_minutes.
 *
 * El tenant y la sede se derivan siempre del usuario autenticado. Los global
 * scopes de App\Traits\BelongsToTenant se encargan del aislamiento por tenant.
 */
class AttendanceQueries
{
    public const MAX_ROWS = 200;

    public const MAX_EXPORT_ROWS = 1000;

    public function __construct(private readonly User $user) {}

    /**
     * @return array<string, mixed>
     */
    public function tardanzasDelDia(?string $fecha, ?string $sede): array
    {
        $fecha = $fecha ?: now()->toDateString();

        if (! $this->sedeAllowed($sede)) {
            return $this->denied();
        }

        [$rows, $total] = $this->lateRows($fecha, $fecha, $sede, null);

        return $this->payload($rows, $total, 'No se encontraron tardanzas para la fecha indicada.');
    }

    /**
     * @return array<string, mixed>
     */
    public function tardanzasPorRango(string $desde, string $hasta, ?string $sede, ?string $departamento): array
    {
        if (! $this->sedeAllowed($sede)) {
            return $this->denied();
        }

        [$rows, $total] = $this->lateRows($desde, $hasta, $sede, $departamento);

        $resumen = collect($rows)
            ->groupBy('empleado_id')
            ->map(fn ($group) => [
                'empleado_id' => $group->first()['empleado_id'],
                'empleado' => $group->first()['empleado'],
                'documento' => $group->first()['documento'],
                'total_tardanzas' => $group->count(),
                'minutos_acumulados' => (int) $group->sum('minutos_tardanza'),
            ])
            ->sortBy('empleado')
            ->values()
            ->all();

        return [
            ...$this->payload($rows, $total, 'No se encontraron tardanzas en el rango indicado.'),
            'resumen_por_empleado' => $resumen,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resumenAsistenciaPorSede(?string $fecha, ?string $desde, ?string $hasta): array
    {
        $dates = $fecha !== null
            ? [$fecha]
            : $this->datesBetween((string) $desde, (string) $hasta);

        $from = $dates[0];
        $to = $dates[count($dates) - 1];

        $employees = Employee::with(['user.sedeRelation'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employeesBySede = $employees->groupBy(fn (Employee $employee) => $this->employeeSede($employee) ?? 'SIN-SEDE');

        $records = AttendanceRecord::with(['employee.user.sedeRelation'])
            ->whereDate('event_time', '>=', $from)
            ->whereDate('event_time', '<=', $to)
            ->get();

        $present = [];
        $lateMinutes = [];
        $lateDays = [];

        foreach ($records as $record) {
            if (! $record->employee) {
                continue;
            }

            $key = $record->employee->id.'|'.$record->event_time->toDateString();
            $present[$key] = true;

            if ($record->is_late) {
                $lateDays[$key] = true;
                $lateMinutes[$key] = max($lateMinutes[$key] ?? 0, abs((int) $record->late_minutes));
            }
        }

        $rows = $employeesBySede->map(function ($sedeEmployees, $sedeCode) use ($dates, $present, $lateDays, $lateMinutes) {
            $presentes = 0;
            $tardanzas = 0;
            $ausencias = 0;
            $minutos = 0;

            foreach ($dates as $date) {
                foreach ($sedeEmployees as $employee) {
                    $key = $employee->id.'|'.$date;

                    if (isset($present[$key])) {
                        $presentes++;
                    } else {
                        $ausencias++;
                    }

                    if (isset($lateDays[$key])) {
                        $tardanzas++;
                        $minutos += $lateMinutes[$key];
                    }
                }
            }

            return [
                'sede' => $sedeCode,
                'dias' => count($dates),
                'empleados' => $sedeEmployees->count(),
                'presentes' => $presentes,
                'tardanzas' => $tardanzas,
                'ausencias' => $ausencias,
                'minutos_tardanza' => $minutos,
            ];
        })->sortBy('sede')->values()->all();

        return $this->payload($rows, count($rows), 'No hay datos de asistencia para el periodo indicado.');
    }

    /**
     * @return array<string, mixed>
     */
    public function marcacionesDeEmpleado(string $identificador, string $desde, string $hasta): array
    {
        $employee = $this->resolveEmployee($identificador);

        if (! $employee) {
            return $this->payload([], 0, 'Empleado no encontrado en su empresa.');
        }

        $query = AttendanceRecord::with(['employee.user.sedeRelation'])
            ->where('employee_id', $employee->id)
            ->whereDate('event_time', '>=', $desde)
            ->whereDate('event_time', '<=', $hasta)
            ->orderBy('event_time');

        $total = (clone $query)->count();

        $rows = $query->limit(self::MAX_ROWS)->get()->map(fn (AttendanceRecord $record) => [
            'hora' => $this->iso($record->event_time),
            'tipo' => $record->attendance_type,
            'sede' => $this->employeeSede($record->employee),
        ])->all();

        return $this->payload($rows, $total, 'No se encontraron marcaciones en el rango indicado.');
    }

    /**
     * @return array<string, mixed>
     */
    public function buscarEmpleado(string $texto): array
    {
        $like = '%'.$texto.'%';

        $query = Employee::with(['department', 'user.sedeRelation'])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('document_id', 'like', $like)
                    ->orWhere('employee_no', 'like', $like);
            })
            ->orderBy('name');

        $total = (clone $query)->count();

        $rows = $query->limit(self::MAX_ROWS)->get()->map(fn (Employee $employee) => [
            'id' => $employee->id,
            'nombre' => $employee->name,
            'documento' => $employee->document_id,
            'departamento' => $employee->department?->name,
            'sede' => $this->employeeSede($employee),
        ])->all();

        return $this->payload($rows, $total, 'No se encontraron empleados con ese criterio.');
    }

    /**
     * @return array<string, mixed>
     */
    public function horarioDeEmpleado(string $identificador): array
    {
        $employee = $this->resolveEmployee($identificador);

        if (! $employee) {
            return $this->payload([], 0, 'Empleado no encontrado en su empresa.');
        }

        $schedule = $employee->currentSchedule();

        if (! $schedule) {
            return $this->payload([], 0, 'El empleado no tiene un horario vigente.');
        }

        $row = [
            'empleado_id' => $employee->id,
            'empleado' => $employee->name,
            ...$this->schedulePayload($schedule),
        ];

        return $this->payload([$row], 1, null);
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulePayload(Schedule $schedule): array
    {
        return [
            'jornada' => $schedule->name,
            'tolerancia_minutos' => (int) $schedule->tolerance_minutes,
            'verifica_tardanza_almuerzo' => (bool) $schedule->check_break_tardiness,
            'intervalos' => $schedule->days()
                ->where('is_working_day', true)
                ->orderBy('day_of_week')
                ->get()
                ->map(fn ($day) => [
                    'dia_semana' => (int) $day->day_of_week,
                    'entrada' => $day->entry_time,
                    'inicio_almuerzo' => $day->break_start_time,
                    'fin_almuerzo' => $day->break_end_time,
                    'salida' => $day->exit_time,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rankingTardanzas(
        string $desde,
        string $hasta,
        ?string $sede,
        ?string $departamento,
        string $agrupar = 'empleado',
        int $limite = 20,
    ): array {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return $this->denied();
        }

        $days = Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) + 1;
        $previousHasta = Carbon::parse($desde)->subDay();
        $previousDesde = $previousHasta->copy()->subDays($days - 1);

        [$currentRows] = $this->lateRows($desde, $hasta, $scope['code'], $departamento, self::MAX_EXPORT_ROWS);
        [$previousRows] = $this->lateRows(
            $previousDesde->toDateString(),
            $previousHasta->toDateString(),
            $scope['code'],
            $departamento,
            self::MAX_EXPORT_ROWS,
        );

        $groupKey = $agrupar === 'departamento'
            ? fn (array $row) => $row['departamento'] ?: 'SIN-DEPARTAMENTO'
            : fn (array $row) => (string) $row['empleado_id'];

        $current = collect($currentRows)->groupBy($groupKey);
        $previous = collect($previousRows)->groupBy($groupKey);

        $rows = $current->keys()
            ->merge($previous->keys())
            ->unique()
            ->map(function ($key) use ($current, $previous, $agrupar) {
                $actual = $current->get($key, collect());
                $anterior = $previous->get($key, collect());
                $sample = $actual->first() ?? $anterior->first();

                $row = [
                    'grupo' => $key,
                    'tardanzas' => $actual->count(),
                    'minutos_tardanza' => (int) $actual->sum('minutos_tardanza'),
                    'tardanzas_justificadas' => $actual->where('justificada', true)->count(),
                    'tardanzas_periodo_anterior' => $anterior->count(),
                    'minutos_periodo_anterior' => (int) $anterior->sum('minutos_tardanza'),
                ];

                $row['variacion_tardanzas'] = $row['tardanzas'] - $row['tardanzas_periodo_anterior'];
                $row['variacion_minutos'] = $row['minutos_tardanza'] - $row['minutos_periodo_anterior'];

                if ($agrupar === 'departamento') {
                    $row['departamento'] = $key;
                    $row['empleados'] = $actual->pluck('empleado_id')->unique()->count();
                } else {
                    $row['empleado_id'] = $sample['empleado_id'] ?? null;
                    $row['empleado'] = $sample['empleado'] ?? 'Empleado no disponible';
                    $row['documento'] = $sample['documento'] ?? null;
                    $row['sede'] = $sample['sede'] ?? null;
                    $row['departamento'] = $sample['departamento'] ?? null;
                }

                return $row;
            })
            ->sortBy([
                ['tardanzas', 'desc'],
                ['minutos_tardanza', 'desc'],
                ['grupo', 'asc'],
            ])
            ->values();

        $total = $rows->count();
        $data = $rows->take($limite)->all();

        return [
            'data' => $data,
            'total' => $total,
            'truncated' => $total > count($data),
            'agrupado_por' => $agrupar,
            'limite' => $limite,
            'periodo_actual' => ['desde' => $desde, 'hasta' => $hasta],
            'periodo_anterior' => [
                'desde' => $previousDesde->toDateString(),
                'hasta' => $previousHasta->toDateString(),
            ],
            ...($total === 0 ? ['mensaje' => 'No se encontraron tardanzas en el periodo indicado.'] : []),
            ...($total > count($data) ? ['mensaje' => 'Se muestran '.$limite.' de '.$total.' grupos con tardanzas.'] : []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ausenciasDelPeriodo(
        string $desde,
        string $hasta,
        ?string $sede,
        ?string $departamento,
        int $limite = self::MAX_ROWS,
    ): array {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return $this->denied();
        }

        $employees = $this->employeeQuery($scope['code'], $departamento)
            ->orderBy('name')
            ->get();

        $defaultSchedule = Schedule::with('days')->where('is_default', true)->first();
        $today = now()->startOfDay();
        $dates = $this->datesBetween($desde, $hasta);
        $records = $this->recordsByEmployeeDay($employees->pluck('id')->all(), $desde, $hasta);

        $notes = EmployeeDailyNote::with('user')
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->whereBetween('date', [$desde, $hasta])
            ->get()
            ->keyBy(fn (EmployeeDailyNote $note) => $note->employee_id.'|'.Carbon::parse($note->date)->toDateString());

        $rows = [];
        $sinHorario = 0;

        foreach ($employees as $employee) {
            $schedule = $this->scheduleFor($employee, $defaultSchedule);

            if (! $schedule) {
                $sinHorario++;

                continue;
            }

            $workingDays = $schedule->days->keyBy('day_of_week');

            foreach ($dates as $date) {
                if (Carbon::parse($date)->greaterThan($today)) {
                    continue;
                }

                $day = $workingDays->get(Carbon::parse($date)->dayOfWeekIso);

                if (! $day || ! $day->is_working_day) {
                    continue;
                }

                if ($records->has($employee->id.'|'.$date)) {
                    continue;
                }

                $note = $notes->get($employee->id.'|'.$date);

                $rows[] = [
                    'empleado_id' => $employee->id,
                    'empleado' => $employee->name,
                    'documento' => $employee->document_id,
                    'sede' => $this->employeeSede($employee),
                    'departamento' => $employee->department?->name,
                    'fecha' => $date,
                    'dia_semana' => $this->dayName((int) Carbon::parse($date)->dayOfWeekIso),
                    'justificada' => $note !== null,
                    'nota' => $note?->note,
                    'nota_autor' => $note?->user?->name,
                ];
            }
        }

        usort($rows, fn ($a, $b) => [$a['fecha'], $a['empleado']] <=> [$b['fecha'], $b['empleado']]);

        return [
            ...$this->payload($rows, count($rows), 'No se encontraron ausencias en el periodo indicado.', $limite),
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
            'empleados_evaluados' => $employees->count(),
            'empleados_sin_horario' => $sinHorario,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function marcacionesIncompletas(
        string $desde,
        string $hasta,
        ?string $sede,
        ?string $departamento,
        int $limite = self::MAX_ROWS,
    ): array {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return $this->denied();
        }

        $rows = $this->incompleteRows($desde, $hasta, $scope['code'], $departamento);

        return [
            ...$this->payload($rows, count($rows), 'No se encontraron marcaciones incompletas en el periodo indicado.', $limite),
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incidenciasDelPeriodo(string $periodo, ?string $sede, ?string $departamento): array
    {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return $this->denied();
        }

        [$desde, $hasta, $etiqueta] = $this->periodBounds($periodo);

        [$lateRows] = $this->lateRows($desde, $hasta, $scope['code'], $departamento, self::MAX_EXPORT_ROWS);

        $tardanzas = collect($lateRows)
            ->groupBy('empleado_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'empleado_id' => $first['empleado_id'],
                    'empleado' => $first['empleado'],
                    'documento' => $first['documento'],
                    'sede' => $first['sede'],
                    'departamento' => $first['departamento'],
                    'veces' => $group->count(),
                    'minutos_tardanza' => (int) $group->sum('minutos_tardanza'),
                    'dias' => $group->pluck('fecha')->unique()->sort()->values()->all(),
                ];
            })
            ->sortBy([
                ['veces', 'desc'],
                ['minutos_tardanza', 'desc'],
                ['empleado', 'asc'],
            ])
            ->values()
            ->all();

        $incomplete = collect($this->incompleteRows($desde, $hasta, $scope['code'], $departamento))
            ->filter(fn (array $row) => in_array($row['problema'], ['sin_salida', 'marcacion_unica'], true));

        $sinSalida = $incomplete
            ->groupBy('empleado_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'empleado_id' => $first['empleado_id'],
                    'empleado' => $first['empleado'],
                    'documento' => $first['documento'],
                    'sede' => $first['sede'],
                    'departamento' => $first['departamento'],
                    'veces' => $group->count(),
                    'dias' => $group->map(fn (array $row) => [
                        'fecha' => $row['fecha'],
                        'problema' => $row['problema'],
                        'primera' => $row['primera'],
                        'ultima' => $row['ultima'],
                    ])->values()->all(),
                ];
            })
            ->sortBy([
                ['veces', 'desc'],
                ['empleado', 'asc'],
            ])
            ->values()
            ->all();

        $totalTardanzas = array_sum(array_column($tardanzas, 'veces'));
        $totalSinSalida = array_sum(array_column($sinSalida, 'veces'));

        $resumen = 'En '.$etiqueta.' ('.$desde.' a '.$hasta.'): '.count($tardanzas).' empleados llegaron tarde ('.$totalTardanzas.' veces) y '.count($sinSalida).' empleados no registraron salida ('.$totalSinSalida.' días).';

        return [
            'periodo' => [
                'tipo' => $periodo,
                'etiqueta' => $etiqueta,
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'data' => $tardanzas,
            'total' => count($tardanzas),
            'truncated' => false,
            'tardanzas' => [
                'total_empleados' => count($tardanzas),
                'total_veces' => $totalTardanzas,
                'data' => $tardanzas,
            ],
            'sin_salida' => [
                'total_empleados' => count($sinSalida),
                'total_veces' => $totalSinSalida,
                'data' => $sinSalida,
            ],
            'resumen' => $resumen,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function incompleteRows(string $desde, string $hasta, ?string $sede, ?string $departamento): array
    {
        $employees = $this->employeeQuery($sede, $departamento)->get()->keyBy('id');
        $defaultSchedule = Schedule::with('days')->where('is_default', true)->first();
        $records = $this->recordsByEmployeeDay($employees->keys()->all(), $desde, $hasta);

        $rows = [];

        foreach ($records as $key => $group) {
            [$employeeId, $date] = explode('|', $key);
            $employee = $employees->get((int) $employeeId);

            if (! $employee) {
                continue;
            }

            $punches = $group->sortBy('event_time')->values();
            $count = $punches->count();
            $first = $punches->first()->event_time;
            $last = $punches->last()->event_time;
            $minutes = (int) $first->diffInMinutes($last);
            $firstType = $punches->first()->attendance_type;
            $lastType = $punches->last()->attendance_type;

            $problem = match (true) {
                $count === 1 => 'marcacion_unica',
                $lastType === 'check_in' => 'sin_salida',
                $firstType === 'check_out' => 'sin_entrada',
                $minutes < 30 => 'salida_no_valida',
                default => null,
            };

            if ($problem === null) {
                continue;
            }

            $calendarDate = Carbon::parse($date);
            $schedule = $this->scheduleFor($employee, $defaultSchedule);
            $day = $schedule?->days->firstWhere('day_of_week', $calendarDate->dayOfWeekIso);

            $rows[] = [
                'empleado_id' => $employee->id,
                'empleado' => $employee->name,
                'documento' => $employee->document_id,
                'sede' => $this->employeeSede($employee),
                'departamento' => $employee->department?->name,
                'fecha' => $date,
                'dia_laborable' => (bool) ($day?->is_working_day),
                'problema' => $problem,
                'marcaciones' => $count,
                'primera' => $this->iso($first),
                'ultima' => $this->iso($last),
                'duracion_minutos' => $minutes,
                'tipos' => $punches->pluck('attendance_type')->unique()->values()->all(),
            ];
        }

        usort($rows, fn ($a, $b) => [$a['fecha'], $a['empleado']] <=> [$b['fecha'], $b['empleado']]);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function horasTrabajadas(
        string $desde,
        string $hasta,
        ?string $sede,
        ?string $departamento,
        int $limite = self::MAX_ROWS,
    ): array {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return $this->denied();
        }

        $employees = $this->employeeQuery($scope['code'], $departamento)
            ->orderBy('name')
            ->get();

        $defaultSchedule = Schedule::with('days')->where('is_default', true)->first();
        $today = now()->startOfDay();
        $dates = $this->datesBetween($desde, $hasta);
        $records = $this->recordsByEmployeeDay($employees->pluck('id')->all(), $desde, $hasta);

        $rows = [];

        foreach ($employees as $employee) {
            $schedule = $this->scheduleFor($employee, $defaultSchedule);
            $workingDays = $schedule?->days->keyBy('day_of_week');

            $programmed = 0;
            $worked = 0;
            $scheduledDays = 0;
            $validDays = 0;
            $missingDays = 0;
            $incompleteDays = 0;

            foreach ($dates as $date) {
                if (Carbon::parse($date)->greaterThan($today)) {
                    continue;
                }

                $day = $workingDays?->get(Carbon::parse($date)->dayOfWeekIso);
                $isWorking = $day && $day->is_working_day;

                if ($isWorking) {
                    $scheduledDays++;
                    $programmed += $this->scheduledMinutes($day);
                }

                $group = $records->get($employee->id.'|'.$date);

                if (! $group) {
                    if ($isWorking) {
                        $missingDays++;
                    }

                    continue;
                }

                $punches = $group->sortBy('event_time')->values();

                if ($punches->count() < 2) {
                    $incompleteDays++;

                    continue;
                }

                $first = $punches->first()->event_time;
                $last = $punches->last()->event_time;
                $minutes = (int) $first->diffInMinutes($last);
                $hasOut = $punches->contains(fn ($record) => $record->attendance_type === 'check_out');

                if ($minutes < 30 && ! $hasOut) {
                    $incompleteDays++;

                    continue;
                }

                $worked += $this->workedMinutes($first, $last, $day);
                $validDays++;
            }

            $rows[] = [
                'empleado_id' => $employee->id,
                'empleado' => $employee->name,
                'documento' => $employee->document_id,
                'sede' => $this->employeeSede($employee),
                'departamento' => $employee->department?->name,
                'sin_horario' => $schedule === null,
                'dias_programados' => $scheduledDays,
                'dias_con_jornada_valida' => $validDays,
                'dias_sin_marcacion' => $missingDays,
                'dias_incompletos' => $incompleteDays,
                'minutos_trabajados' => $worked,
                'horas_trabajadas' => round($worked / 60, 2),
                'minutos_programados' => $programmed,
                'diferencia_minutos' => $worked - $programmed,
                'promedio_diario_minutos' => $validDays > 0 ? (int) round($worked / $validDays) : 0,
            ];
        }

        return [
            ...$this->payload($rows, count($rows), 'No hay empleados para el periodo indicado.', $limite),
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
            'zona_horaria' => config('app.timezone'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detalleDeEmpleado(string $identificador): array
    {
        $employee = $this->resolveEmployee($identificador);

        if (! $employee) {
            return $this->payload([], 0, 'Empleado no encontrado en su empresa.');
        }

        $schedule = $employee->currentSchedule();
        $last = $employee->attendanceRecords()->latest('event_time')->first();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $lates = AttendanceRecord::where('employee_id', $employee->id)
            ->where('is_late', true)
            ->whereBetween('event_time', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
            ->orderBy('event_time')
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->event_time->toDateString())
            ->map(fn ($group) => $group->first());

        $monthNotes = EmployeeDailyNote::where('employee_id', $employee->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get()
            ->keyBy(fn (EmployeeDailyNote $note) => Carbon::parse($note->date)->toDateString());

        $todayRecords = $employee->attendanceRecords()
            ->whereBetween('event_time', [$today.' 00:00:00', $today.' 23:59:59'])
            ->orderBy('event_time')
            ->get();

        $notes = EmployeeDailyNote::with('user')
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $detail = [
            'empleado_id' => $employee->id,
            'empleado' => $employee->name,
            'documento' => $employee->document_id,
            'activo' => (bool) $employee->is_active,
            'puesto' => $employee->position,
            'sede' => $this->employeeSede($employee),
            'departamento' => $employee->department?->name,
            'horario' => $schedule ? $this->schedulePayload($schedule) : null,
            'ultima_marcacion' => $last ? [
                'hora' => $this->iso($last->event_time),
                'tipo' => $last->attendance_type,
                'sede' => $this->employeeSede($employee),
            ] : null,
            'marcaciones_hoy' => [
                'total' => $todayRecords->count(),
                'primera' => $todayRecords->first() ? $this->iso($todayRecords->first()->event_time) : null,
                'ultima' => $todayRecords->last() ? $this->iso($todayRecords->last()->event_time) : null,
            ],
            'tardanzas_mes_actual' => [
                'total' => $lates->count(),
                'minutos' => (int) $lates->sum(fn (AttendanceRecord $record) => abs((int) $record->late_minutes)),
                'justificadas' => $lates->keys()->filter(fn ($date) => $monthNotes->has($date))->count(),
            ],
            'notas_recientes' => $notes->map(fn (EmployeeDailyNote $note) => [
                'fecha' => Carbon::parse($note->date)->toDateString(),
                'nota' => $note->note,
                'autor' => $note->user?->name,
            ])->all(),
        ];

        return $this->payload([$detail], 1, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogoSedesDepartamentos(): array
    {
        $allowed = $this->userSedeCodes();
        $sedesQuery = Sede::orderBy('code');

        if ($allowed !== null) {
            $sedesQuery->where(function (Builder $query) use ($allowed) {
                $query->whereIn('code', $allowed)->orWhereIn('name', $allowed);
            });
        }

        $sedes = $sedesQuery->get();
        $sedeCode = $this->effectiveSede(null)['code'];

        $departments = Department::with('schedule')
            ->withCount(['employees' => function (Builder $query) use ($sedeCode) {
                $query->where('is_active', true);
                $this->constrainEmployeeSede($query, $sedeCode);
            }])
            ->orderBy('name')
            ->get();

        $rows = $sedes->map(fn (Sede $sede) => [
            'tipo' => 'sede',
            'codigo' => $sede->code,
            'nombre' => $sede->name,
            'descripcion' => $sede->description,
        ])->concat($departments->map(fn (Department $department) => [
            'tipo' => 'departamento',
            'nombre' => $department->name,
            'horario' => $department->schedule?->name,
            'empleados_activos' => (int) $department->employees_count,
        ]))->values()->all();

        return $this->payload($rows, count($rows), 'No hay sedes ni departamentos disponibles.');
    }

    /**
     * @return array<string, mixed>
     */
    public function exportarReporte(
        string $tipo,
        string $desde,
        string $hasta,
        ?string $sede,
        ?string $departamento,
    ): array {
        $scope = $this->effectiveSede($sede);

        if (! $scope['allowed']) {
            return [...$this->denied(), 'formato' => 'csv', 'csv' => null];
        }

        $limit = self::MAX_EXPORT_ROWS;

        if ($tipo === 'ausencias') {
            $result = $this->ausenciasDelPeriodo($desde, $hasta, $scope['code'], $departamento, $limit);
            $rows = $result['data'] ?? [];
            $total = $result['total'] ?? count($rows);
            $headers = ['Empleado', 'Documento', 'Sede', 'Departamento', 'Fecha', 'Dia', 'Justificada', 'Nota'];
            $line = fn (array $row) => [
                $row['empleado'], $row['documento'], $row['sede'], $row['departamento'],
                $row['fecha'], $row['dia_semana'], $row['justificada'] ? 'Si' : 'No', $row['nota'],
            ];
        } elseif ($tipo === 'horas_trabajadas') {
            $result = $this->horasTrabajadas($desde, $hasta, $scope['code'], $departamento, $limit);
            $rows = $result['data'] ?? [];
            $total = $result['total'] ?? count($rows);
            $headers = ['Empleado', 'Documento', 'Sede', 'Departamento', 'Dias programados', 'Dias con jornada', 'Dias sin marcacion', 'Dias incompletos', 'Minutos trabajados', 'Horas trabajadas', 'Minutos programados', 'Diferencia minutos'];
            $line = fn (array $row) => [
                $row['empleado'], $row['documento'], $row['sede'], $row['departamento'],
                $row['dias_programados'], $row['dias_con_jornada_valida'], $row['dias_sin_marcacion'],
                $row['dias_incompletos'], $row['minutos_trabajados'], $row['horas_trabajadas'],
                $row['minutos_programados'], $row['diferencia_minutos'],
            ];
        } else {
            [$rows, $total] = $this->lateRows($desde, $hasta, $scope['code'], $departamento, $limit);
            $headers = ['Empleado', 'Documento', 'Sede', 'Departamento', 'Fecha', 'Hora entrada', 'Minutos tardanza', 'Justificada'];
            $line = fn (array $row) => [
                $row['empleado'], $row['documento'], $row['sede'], $row['departamento'],
                $row['fecha'], $row['hora_entrada'], $row['minutos_tardanza'], $row['justificada'] ? 'Si' : 'No',
            ];
        }

        $csv = $this->toCsv($headers, array_map($line, $rows));
        $truncated = $total > count($rows);

        return [
            'formato' => 'csv',
            'delimitador' => ';',
            'tipo' => $tipo,
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
            'filas' => count($rows),
            'total' => $total,
            'truncated' => $truncated,
            'csv' => $csv,
            'mensaje' => $truncated
                ? 'CSV limitado a '.$limit.' filas de un total de '.$total.'.'
                : 'CSV generado con '.count($rows).' filas.',
        ];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function lateRows(string $desde, string $hasta, ?string $sede, ?string $departamento, int $limite = self::MAX_ROWS): array
    {
        $query = AttendanceRecord::with(['employee.department', 'employee.user.sedeRelation'])
            ->where('is_late', true)
            ->whereDate('event_time', '>=', $desde)
            ->whereDate('event_time', '<=', $hasta)
            ->orderBy('event_time');

        $query->whereHas('employee', function (Builder $employee) use ($sede, $departamento) {
            $this->constrainEmployeeSede($employee, $sede);

            if ($departamento !== null) {
                $employee->whereHas('department', fn (Builder $department) => $department->where('name', $departamento));
            }
        });

        $records = $query->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->employee_id.'|'.$record->event_time->toDateString())
            ->map(fn ($group) => $group->first())
            ->filter(fn (AttendanceRecord $record) => $record->employee !== null)
            ->values();

        $notes = EmployeeDailyNote::query()
            ->whereIn('employee_id', $records->pluck('employee_id')->unique()->all())
            ->whereDate('date', '>=', $desde)
            ->whereDate('date', '<=', $hasta)
            ->get()
            ->keyBy(fn (EmployeeDailyNote $note) => $note->employee_id.'|'.Carbon::parse($note->date)->toDateString());

        $rows = $records->map(fn (AttendanceRecord $record) => [
            'empleado_id' => $record->employee->id,
            'empleado' => $record->employee->name,
            'documento' => $record->employee->document_id,
            'sede' => $this->employeeSede($record->employee),
            'departamento' => $record->employee->department?->name,
            'fecha' => $record->event_time->toDateString(),
            'hora_entrada' => $this->iso($record->event_time),
            'minutos_tardanza' => abs((int) $record->late_minutes),
            'justificada' => $notes->has($record->employee->id.'|'.$record->event_time->toDateString()),
        ])->values();

        $total = $rows->count();

        return [$rows->take($limite)->all(), $total];
    }

    /**
     * Sede efectiva de la petición: un superadmin puede pedir cualquiera;
     * el resto queda siempre acotado a la sede de su token.
     *
     * @return array{allowed: bool, code: ?string}
     */
    private function effectiveSede(?string $sede): array
    {
        if ($this->user->is_superadmin) {
            return ['allowed' => true, 'code' => ($sede === null || $sede === '') ? null : $sede];
        }

        $own = $this->user->sedeRelation?->code ?? $this->user->sede;

        if ($sede !== null && $sede !== '') {
            return ['allowed' => $this->sedeAllowed($sede), 'code' => $own ?: $sede];
        }

        return ['allowed' => true, 'code' => $own];
    }

    private function employeeQuery(?string $sede, ?string $departamento): Builder
    {
        $query = Employee::with([
            'department.schedule.days',
            'user.sedeRelation',
            'employeeSchedules.schedule.days',
        ])->where('is_active', true);

        $query->where(function (Builder $employee) use ($sede, $departamento) {
            $this->constrainEmployeeSede($employee, $sede);

            if ($departamento !== null) {
                $employee->whereHas('department', fn (Builder $department) => $department->where('name', $departamento));
            }
        });

        return $query;
    }

    private function scheduleFor(Employee $employee, ?Schedule $default): ?Schedule
    {
        if ($employee->department?->schedule) {
            return $employee->department->schedule;
        }

        $today = now()->toDateString();
        $assignment = $employee->employeeSchedules
            ->filter(fn ($item) => $item->end_date === null || $item->end_date->toDateString() >= $today)
            ->sortByDesc(fn ($item) => $item->start_date?->timestamp ?? 0)
            ->first();

        return $assignment?->schedule ?? $default;
    }

    private function constrainEmployeeSede(Builder $employee, ?string $sede): void
    {
        if ($sede === null || $sede === '') {
            return;
        }

        $employee->whereHas('user', function (Builder $user) use ($sede) {
            $user->where(function (Builder $scope) use ($sede) {
                $scope->where('sede', $sede)
                    ->orWhereHas('sedeRelation', function (Builder $relation) use ($sede) {
                        $relation->where('code', $sede)->orWhere('name', $sede);
                    });
            });
        });
    }

    private function recordsByEmployeeDay(array $employeeIds, string $desde, string $hasta): Collection
    {
        if ($employeeIds === []) {
            return collect();
        }

        return AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereBetween('event_time', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->orderBy('event_time')
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->employee_id.'|'.$record->event_time->toDateString());
    }

    private function scheduledMinutes(ScheduleDay $day): int
    {
        if (! $day->entry_time || ! $day->exit_time) {
            return 0;
        }

        $entry = Carbon::parse('2000-01-01 '.$day->entry_time);
        $exit = Carbon::parse('2000-01-01 '.$day->exit_time);

        if ($exit->lessThanOrEqualTo($entry)) {
            return 0;
        }

        $minutes = (int) $entry->diffInMinutes($exit);

        if ($day->break_start_time && $day->break_end_time) {
            $breakStart = Carbon::parse('2000-01-01 '.$day->break_start_time);
            $breakEnd = Carbon::parse('2000-01-01 '.$day->break_end_time);

            if ($breakEnd->greaterThan($breakStart)) {
                $minutes -= (int) $breakStart->diffInMinutes($breakEnd);
            }
        }

        return max(0, $minutes);
    }

    private function workedMinutes(Carbon $first, Carbon $last, ?ScheduleDay $day): int
    {
        $minutes = (int) $first->diffInMinutes($last);

        if ($day?->break_start_time && $day?->break_end_time) {
            $breakStart = $first->copy()->setTimeFromTimeString($day->break_start_time);
            $breakEnd = $first->copy()->setTimeFromTimeString($day->break_end_time);

            if ($breakEnd->greaterThan($breakStart) && $first->lessThanOrEqualTo($breakStart) && $last->greaterThanOrEqualTo($breakEnd)) {
                $minutes -= (int) $breakStart->diffInMinutes($breakEnd);
            }
        }

        return max(0, $minutes);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function periodBounds(string $periodo): array
    {
        $today = now()->toDateString();

        if ($periodo === 'mes') {
            return [now()->startOfMonth()->toDateString(), $today, 'mes en curso'];
        }

        return [now()->subDays(6)->toDateString(), $today, 'últimos 7 días'];
    }

    private function dayName(int $dayOfWeek): string
    {
        return [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'][$dayOfWeek] ?? '';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function toCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?? ''),
                $row,
            ), ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }

    private function resolveEmployee(string $identificador): ?Employee
    {
        return Employee::with(['department', 'user.sedeRelation'])
            ->where(function (Builder $query) use ($identificador) {
                $query->where('id', $identificador)
                    ->orWhere('document_id', $identificador)
                    ->orWhere('employee_no', $identificador);
            })
            ->first();
    }

    private function employeeSede(?Employee $employee): ?string
    {
        if (! $employee) {
            return null;
        }

        $user = $employee->user;

        return $user?->sedeRelation?->code ?? $user?->sede;
    }

    /**
     * @return array<int, string>|null null = sin restricción (superadmin)
     */
    private function userSedeCodes(): ?array
    {
        if ($this->user->is_superadmin) {
            return null;
        }

        $codes = [];

        if ($this->user->sedeRelation) {
            $codes[] = $this->user->sedeRelation->code;
            $codes[] = $this->user->sedeRelation->name;
        } elseif ($this->user->sede) {
            $codes[] = $this->user->sede;
        }

        return array_values(array_unique(array_filter($codes)));
    }

    private function sedeAllowed(?string $sede): bool
    {
        if ($sede === null || $sede === '') {
            return true;
        }

        $allowed = $this->userSedeCodes();

        if ($allowed === null) {
            return true;
        }

        foreach ($allowed as $code) {
            if (strcasecmp($code, $sede) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function datesBetween(string $desde, string $hasta): array
    {
        $dates = [];
        $cursor = Carbon::parse($desde)->startOfDay();
        $end = Carbon::parse($hasta)->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    private function iso(Carbon $time): string
    {
        return $time->copy()->setTimezone(config('app.timezone'))->toIso8601String();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function payload(array $rows, int $total, ?string $emptyMessage, int $limite = self::MAX_ROWS): array
    {
        $truncated = $total > $limite;

        $payload = [
            'data' => array_slice($rows, 0, $limite),
            'total' => $total,
            'truncated' => $truncated,
        ];

        if ($truncated) {
            $payload['mensaje'] = 'Resultado truncado a '.$limite.' filas de un total de '.$total.'.';
        } elseif ($total === 0 && $emptyMessage !== null) {
            $payload['mensaje'] = $emptyMessage;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function denied(): array
    {
        return [
            'data' => [],
            'total' => 0,
            'truncated' => false,
            'mensaje' => 'La sede solicitada no está disponible para este usuario.',
        ];
    }
}
