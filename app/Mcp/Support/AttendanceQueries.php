<?php

namespace App\Mcp\Support;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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

        return $this->payload([$row], 1, null);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function lateRows(string $desde, string $hasta, ?string $sede, ?string $departamento): array
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
        ])->all();

        return [$rows, count($rows)];
    }

    private function constrainEmployeeSede(Builder $employee, ?string $sede): void
    {
        if ($sede === null || $sede === '') {
            return;
        }

        $employee->whereHas('user', function (Builder $user) use ($sede) {
            $user->where('sede', $sede)
                ->orWhereHas('sedeRelation', function (Builder $relation) use ($sede) {
                    $relation->where('code', $sede)->orWhere('name', $sede);
                });
        });
    }

    private function resolveEmployee(string $identificador): ?Employee
    {
        return Employee::with(['department', 'user.sedeRelation'])
            ->where('id', $identificador)
            ->orWhere('document_id', $identificador)
            ->orWhere('employee_no', $identificador)
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
    private function payload(array $rows, int $total, ?string $emptyMessage): array
    {
        $truncated = $total > self::MAX_ROWS;

        $payload = [
            'data' => array_slice($rows, 0, self::MAX_ROWS),
            'total' => $total,
            'truncated' => $truncated,
        ];

        if ($truncated) {
            $payload['mensaje'] = 'Resultado truncado a '.self::MAX_ROWS.' filas de un total de '.$total.'.';
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
