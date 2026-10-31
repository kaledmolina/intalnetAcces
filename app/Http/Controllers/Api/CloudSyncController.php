<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloudSyncController extends Controller
{
    /**
     * Autenticar al usuario de la aplicación de escritorio verificando su correo y token HMAC.
     */
    protected function authenticateDesktopUser(Request $request): ?User
    {
        $email = $request->header('X-Desktop-User') ?: $request->input('email');
        $token = $request->header('X-Desktop-Token') ?: $request->input('token');

        if (!$email || !$token) {
            return null;
        }

        $user = User::where('email', $email)->first();
        if (!$user || !$user->is_active) {
            return null;
        }

        $expectedToken = hash_hmac('sha256', $user->email . '|' . $user->password, config('app.key'));
        if (!hash_equals($expectedToken, $token)) {
            return null;
        }

        return $user;
    }

    /**
     * PUSH: Recibe datos enviados desde la aplicación de escritorio y los sincroniza en la nube central.
     * (Marcaciones de huelleros locales, empleados nuevos/modificados, dispositivos, notas).
     */
    public function push(Request $request)
    {
        $user = $this->authenticateDesktopUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Token de sincronización inválido o usuario inactivo en la nube.',
            ], 401);
        }

        Auth::setUser($user);

        $counts = [
            'schedules' => 0,
            'departments' => 0,
            'employees' => 0,
            'devices' => 0,
            'attendance_records' => 0,
            'daily_notes' => 0,
        ];

        DB::beginTransaction();
        try {
            // 1. Sincronizar Horarios si vienen
            if ($request->has('schedules')) {
                foreach ($request->input('schedules', []) as $schData) {
                    if (empty($schData['name'])) continue;

                    $schedule = Schedule::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $user->id, 'name' => $schData['name']],
                        [
                            'tolerance_minutes' => (int) ($schData['tolerance_minutes'] ?? 5),
                            'is_default' => (bool) ($schData['is_default'] ?? false),
                            'check_break_tardiness' => (bool) ($schData['check_break_tardiness'] ?? false),
                        ]
                    );

                    if (!empty($schData['days']) && is_array($schData['days'])) {
                        foreach ($schData['days'] as $day) {
                            ScheduleDay::updateOrCreate(
                                [
                                    'schedule_id' => $schedule->id,
                                    'day_of_week' => (int) $day['day_of_week'],
                                ],
                                [
                                    'is_working_day' => (bool) ($day['is_working_day'] ?? false),
                                    'entry_time' => $day['entry_time'] ?? null,
                                    'break_start_time' => $day['break_start_time'] ?? null,
                                    'break_end_time' => $day['break_end_time'] ?? null,
                                    'exit_time' => $day['exit_time'] ?? null,
                                ]
                            );
                        }
                    }
                    $counts['schedules']++;
                }
            }

            // 2. Sincronizar Departamentos
            if ($request->has('departments')) {
                foreach ($request->input('departments', []) as $deptData) {
                    if (empty($deptData['name'])) continue;

                    $scheduleId = null;
                    if (!empty($deptData['schedule_name'])) {
                        $sch = Schedule::withoutGlobalScopes()
                            ->where('user_id', $user->id)
                            ->where('name', $deptData['schedule_name'])
                            ->first();
                        $scheduleId = $sch?->id;
                    }

                    Department::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $user->id, 'name' => $deptData['name']],
                        ['schedule_id' => $scheduleId]
                    );
                    $counts['departments']++;
                }
            }

            // 3. Sincronizar Empleados
            if ($request->has('employees')) {
                foreach ($request->input('employees', []) as $empData) {
                    if (empty($empData['employee_no'])) continue;

                    $deptId = null;
                    if (!empty($empData['department_name'])) {
                        $dept = Department::withoutGlobalScopes()
                            ->where('user_id', $user->id)
                            ->where('name', $empData['department_name'])
                            ->first();
                        $deptId = $dept?->id;
                    }

                    Employee::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $user->id, 'employee_no' => (string) $empData['employee_no']],
                        [
                            'name' => $empData['name'] ?? 'Sin Nombre',
                            'document_id' => $empData['document_id'] ?? null,
                            'department_id' => $deptId,
                            'position' => $empData['position'] ?? null,
                            'phone' => $empData['phone'] ?? null,
                            'email' => $empData['email'] ?? null,
                            'is_active' => isset($empData['is_active']) ? (bool) $empData['is_active'] : true,
                            'has_fingerprint' => (bool) ($empData['has_fingerprint'] ?? false),
                        ]
                    );
                    $counts['employees']++;
                }
            }

            // 4. Sincronizar Dispositivos
            if ($request->has('devices')) {
                foreach ($request->input('devices', []) as $devData) {
                    if (empty($devData['ip_address'])) continue;

                    Device::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $user->id, 'ip_address' => $devData['ip_address']],
                        [
                            'name' => $devData['name'] ?? 'Huellero ' . $devData['ip_address'],
                            'port' => (int) ($devData['port'] ?? 80),
                            'protocol' => $devData['protocol'] ?? 'http',
                            'username' => $devData['username'] ?? 'admin',
                            'password' => $devData['password'] ?? '',
                            'location' => $devData['location'] ?? null,
                            'status' => $devData['status'] ?? 'offline',
                            'last_sync_at' => !empty($devData['last_sync_at']) ? Carbon::parse($devData['last_sync_at']) : now(),
                        ]
                    );
                    $counts['devices']++;
                }
            }

            // 5. Sincronizar Marcaciones de Asistencia (AttendanceRecords)
            if ($request->has('attendance_records')) {
                foreach ($request->input('attendance_records', []) as $rec) {
                    if (empty($rec['employee_no']) || empty($rec['event_time'])) continue;

                    $employee = Employee::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where('employee_no', (string) $rec['employee_no'])
                        ->first();

                    $device = null;
                    if (!empty($rec['device_name'])) {
                        $device = Device::withoutGlobalScopes()
                            ->where('user_id', $user->id)
                            ->where('name', $rec['device_name'])
                            ->first();
                    }

                    $parsedEventTime = Carbon::parse($rec['event_time'])->format('Y-m-d H:i:s');

                    AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'employee_no' => (string) $rec['employee_no'],
                            'event_time' => $parsedEventTime,
                        ],
                        [
                            'employee_id' => $employee?->id,
                            'device_id' => $device?->id,
                            'card_no' => $rec['card_no'] ?? null,
                            'attendance_type' => $rec['attendance_type'] ?? 'auto',
                            'is_late' => (bool) ($rec['is_late'] ?? false),
                            'late_minutes' => (int) ($rec['late_minutes'] ?? 0),
                            'hikvision_event_id' => $rec['hikvision_event_id'] ?? null,
                            'raw_data' => $rec['raw_data'] ?? null,
                        ]
                    );
                    $counts['attendance_records']++;
                }
            }

            // 6. Sincronizar Notas Diarias de Empleados
            if ($request->has('daily_notes')) {
                foreach ($request->input('daily_notes', []) as $note) {
                    if (empty($note['employee_no']) || empty($note['date'])) continue;

                    $employee = Employee::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where('employee_no', (string) $note['employee_no'])
                        ->first();

                    if ($employee) {
                        EmployeeDailyNote::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'employee_id' => $employee->id,
                                'date' => Carbon::parse($note['date'])->format('Y-m-d'),
                            ],
                            [
                                'note' => $note['note'] ?? '',
                            ]
                        );
                        $counts['daily_notes']++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización PUSH completada exitosamente en la nube.',
                'counts' => $counts,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en CloudSyncController::push: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la sincronización en la nube: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PULL: Entrega a la aplicación de escritorio los datos centrales de la nube.
     * (Horarios, departamentos, empleados actualizados en web, marcaciones previas).
     */
    public function pull(Request $request)
    {
        $user = $this->authenticateDesktopUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Token de sincronización inválido o usuario inactivo en la nube.',
            ], 401);
        }

        Auth::setUser($user);

        try {
            // 1. Horarios con sus días
            $schedules = Schedule::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->with('days')
                ->get()
                ->map(function ($s) {
                    return [
                        'name' => $s->name,
                        'tolerance_minutes' => $s->tolerance_minutes,
                        'is_default' => (bool) $s->is_default,
                        'check_break_tardiness' => (bool) $s->check_break_tardiness,
                        'days' => $s->days->map(function ($d) {
                            return [
                                'day_of_week' => $d->day_of_week,
                                'is_working_day' => (bool) $d->is_working_day,
                                'entry_time' => $d->entry_time,
                                'break_start_time' => $d->break_start_time,
                                'break_end_time' => $d->break_end_time,
                                'exit_time' => $d->exit_time,
                            ];
                        })->toArray(),
                    ];
                });

            // 2. Departamentos
            $departments = Department::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->with('schedule')
                ->get()
                ->map(function ($d) {
                    return [
                        'name' => $d->name,
                        'schedule_name' => $d->schedule?->name,
                    ];
                });

            // 3. Empleados
            $employees = Employee::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->with('department')
                ->get()
                ->map(function ($e) {
                    return [
                        'employee_no' => $e->employee_no,
                        'name' => $e->name,
                        'document_id' => $e->document_id,
                        'department_name' => $e->department?->name,
                        'position' => $e->position,
                        'phone' => $e->phone,
                        'email' => $e->email,
                        'is_active' => (bool) $e->is_active,
                        'has_fingerprint' => (bool) $e->has_fingerprint,
                    ];
                });

            // 4. Dispositivos registrados en la nube
            $devices = Device::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->get()
                ->map(function ($dev) {
                    return [
                        'name' => $dev->name,
                        'ip_address' => $dev->ip_address,
                        'port' => $dev->port,
                        'protocol' => $dev->protocol,
                        'username' => $dev->username,
                        'password' => $dev->password,
                        'location' => $dev->location,
                        'status' => $dev->status,
                        'last_sync_at' => $dev->last_sync_at?->format('Y-m-d H:i:s'),
                    ];
                });

            // 5. Marcaciones (últimos 60 días)
            $since = $request->input('since') 
                ? Carbon::parse($request->input('since')) 
                : Carbon::now()->subDays(60);

            $attendanceRecords = AttendanceRecord::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('event_time', '>=', $since)
                ->with('device')
                ->get()
                ->map(function ($rec) {
                    return [
                        'employee_no' => $rec->employee_no,
                        'card_no' => $rec->card_no,
                        'event_time' => $rec->event_time->format('Y-m-d H:i:s'),
                        'attendance_type' => $rec->attendance_type,
                        'is_late' => (bool) $rec->is_late,
                        'late_minutes' => (int) $rec->late_minutes,
                        'hikvision_event_id' => $rec->hikvision_event_id,
                        'raw_data' => $rec->raw_data,
                        'device_name' => $rec->device?->name,
                    ];
                });

            // 6. Notas diarias
            $dailyNotes = EmployeeDailyNote::where('user_id', $user->id)
                ->where('date', '>=', $since->format('Y-m-d'))
                ->with('employee')
                ->get()
                ->map(function ($n) {
                    return [
                        'employee_no' => $n->employee?->employee_no,
                        'date' => $n->date,
                        'note' => $n->note,
                    ];
                })
                ->filter(fn($n) => !empty($n['employee_no']))
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Datos centrales obtenidos exitosamente.',
                'data' => [
                    'schedules' => $schedules,
                    'departments' => $departments,
                    'employees' => $employees,
                    'devices' => $devices,
                    'attendance_records' => $attendanceRecords,
                    'daily_notes' => $dailyNotes,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en CloudSyncController::pull: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al consultar datos en la nube: ' . $e->getMessage(),
            ], 500);
        }
    }
}
