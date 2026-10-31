<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudSyncService
{
    /**
     * Obtener la URL base de la nube central.
     */
    protected function getCloudUrl(): string
    {
        return rtrim(env('CLOUD_AUTH_URL', 'https://access.intalnet.com'), '/');
    }

    /**
     * Obtener el correo y el token de sincronización del usuario actual.
     */
    protected function getCredentials(): ?array
    {
        $user = Auth::user();
        $email = $user?->email 
            ?? session('cloud_sync_email') 
            ?? Cache::get('cloud_sync_last_email');

        if (!$email) {
            return null;
        }

        $token = session('cloud_sync_token') 
            ?? Cache::get('cloud_sync_token_' . $email);

        if (!$token) {
            return null;
        }

        return [
            'email' => $email,
            'token' => $token,
        ];
    }

    /**
     * Guardar el token de sincronización en caché persistente y sesión.
     */
    public function storeCredentials(string $email, string $token): void
    {
        session([
            'cloud_sync_email' => $email,
            'cloud_sync_token' => $token,
        ]);

        Cache::forever('cloud_sync_last_email', $email);
        Cache::forever('cloud_sync_token_' . $email, $token);
    }

    /**
     * PUSH: Enviar datos locales (marcaciones de huelleros, empleados, dispositivos) a la nube central.
     */
    public function push(): array
    {
        $creds = $this->getCredentials();
        if (!$creds) {
            return [
                'success' => false,
                'message' => 'No hay sesión activa autorizada por la nube central para sincronizar.',
            ];
        }

        try {
            $user = Auth::user();
            $userId = $user?->id;

            // Recopilar horarios locales
            $schedules = Schedule::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
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
                })->toArray();

            // Recopilar departamentos locales
            $departments = Department::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->with('schedule')
                ->get()
                ->map(function ($d) {
                    return [
                        'name' => $d->name,
                        'schedule_name' => $d->schedule?->name,
                    ];
                })->toArray();

            // Recopilar empleados locales
            $employees = Employee::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
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
                })->toArray();

            // Recopilar dispositivos huelleros configurados
            $devices = Device::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
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
                })->toArray();

            // Recopilar marcaciones de los últimos 30 días
            $records = AttendanceRecord::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->where('event_time', '>=', now()->subDays(30))
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
                })->toArray();

            // Recopilar notas de empleados
            $notes = EmployeeDailyNote::withoutGlobalScopes()
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
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
                ->values()
                ->toArray();

            $url = "{$this->getCloudUrl()}/api/v1/desktop/sync-push";

            $response = Http::timeout(15)->post($url, [
                'email' => $creds['email'],
                'token' => $creds['token'],
                'schedules' => $schedules,
                'departments' => $departments,
                'employees' => $employees,
                'devices' => $devices,
                'attendance_records' => $records,
                'daily_notes' => $notes,
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data['success'])) {
                return [
                    'success' => true,
                    'message' => 'Marcaciones y datos locales subidos a la nube con éxito.',
                    'counts' => $data['counts'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'La nube rechazó la sincronización.',
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::info('Modo sin conexión: No se pudo conectar a la nube central (' . $e->getMessage() . ')');
            return [
                'success' => false,
                'message' => 'Sin conexión a internet con la nube. Los registros quedan guardados localmente.',
            ];
        } catch (\Throwable $t) {
            Log::error('Error en CloudSyncService::push: ' . $t->getMessage());
            return [
                'success' => false,
                'message' => 'Error durante el envío a la nube: ' . $t->getMessage(),
            ];
        }
    }

    /**
     * PULL: Descargar de la nube central los departamentos, horarios y empleados y actualizarlos en la PC.
     */
    public function pull(): array
    {
        $creds = $this->getCredentials();
        if (!$creds) {
            return [
                'success' => false,
                'message' => 'No hay credenciales activas para consultar la nube.',
            ];
        }

        try {
            $url = "{$this->getCloudUrl()}/api/v1/desktop/sync-pull";

            $response = Http::timeout(15)->post($url, [
                'email' => $creds['email'],
                'token' => $creds['token'],
            ]);

            $result = $response->json();

            if (!$response->successful() || empty($result['success']) || empty($result['data'])) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'No se pudo obtener datos de la nube.',
                ];
            }

            $data = $result['data'];
            $user = Auth::user();
            $userId = $user?->id ?? 1;

            DB::beginTransaction();

            // 1. Integrar Horarios
            if (!empty($data['schedules'])) {
                foreach ($data['schedules'] as $sch) {
                    $localSch = Schedule::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $userId, 'name' => $sch['name']],
                        [
                            'tolerance_minutes' => (int) ($sch['tolerance_minutes'] ?? 5),
                            'is_default' => (bool) ($sch['is_default'] ?? false),
                            'check_break_tardiness' => (bool) ($sch['check_break_tardiness'] ?? false),
                        ]
                    );

                    if (!empty($sch['days'])) {
                        foreach ($sch['days'] as $d) {
                            ScheduleDay::updateOrCreate(
                                [
                                    'schedule_id' => $localSch->id,
                                    'day_of_week' => (int) $d['day_of_week'],
                                ],
                                [
                                    'is_working_day' => (bool) ($d['is_working_day'] ?? false),
                                    'entry_time' => $d['entry_time'] ?? null,
                                    'break_start_time' => $d['break_start_time'] ?? null,
                                    'break_end_time' => $d['break_end_time'] ?? null,
                                    'exit_time' => $d['exit_time'] ?? null,
                                ]
                            );
                        }
                    }
                }
            }

            // 2. Integrar Departamentos
            if (!empty($data['departments'])) {
                foreach ($data['departments'] as $dept) {
                    $scheduleId = null;
                    if (!empty($dept['schedule_name'])) {
                        $sch = Schedule::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('name', $dept['schedule_name'])
                            ->first();
                        $scheduleId = $sch?->id;
                    }

                    Department::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $userId, 'name' => $dept['name']],
                        ['schedule_id' => $scheduleId]
                    );
                }
            }

            // 3. Integrar Empleados
            if (!empty($data['employees'])) {
                foreach ($data['employees'] as $emp) {
                    $deptId = null;
                    if (!empty($emp['department_name'])) {
                        $dept = Department::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('name', $emp['department_name'])
                            ->first();
                        $deptId = $dept?->id;
                    }

                    Employee::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $userId, 'employee_no' => (string) $emp['employee_no']],
                        [
                            'name' => $emp['name'] ?? 'Sin Nombre',
                            'document_id' => $emp['document_id'] ?? null,
                            'department_id' => $deptId,
                            'position' => $emp['position'] ?? null,
                            'phone' => $emp['phone'] ?? null,
                            'email' => $emp['email'] ?? null,
                            'is_active' => isset($emp['is_active']) ? (bool) $emp['is_active'] : true,
                            'has_fingerprint' => (bool) ($emp['has_fingerprint'] ?? false),
                        ]
                    );
                }
            }

            // 4. Integrar Dispositivos si existen en la nube
            if (!empty($data['devices'])) {
                foreach ($data['devices'] as $dev) {
                    Device::withoutGlobalScopes()->updateOrCreate(
                        ['user_id' => $userId, 'ip_address' => $dev['ip_address']],
                        [
                            'name' => $dev['name'] ?? 'Huellero ' . $dev['ip_address'],
                            'port' => (int) ($dev['port'] ?? 80),
                            'protocol' => $dev['protocol'] ?? 'http',
                            'username' => $dev['username'] ?? 'admin',
                            'password' => $dev['password'] ?? '',
                            'location' => $dev['location'] ?? null,
                        ]
                    );
                }
            }

            // 5. Integrar Marcaciones previas de la nube si no existen en local
            if (!empty($data['attendance_records'])) {
                foreach ($data['attendance_records'] as $rec) {
                    $emp = Employee::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('employee_no', (string) $rec['employee_no'])
                        ->first();

                    $device = null;
                    if (!empty($rec['device_name'])) {
                        $device = Device::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('name', $rec['device_name'])
                            ->first();
                    }

                    AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
                        [
                            'user_id' => $userId,
                            'employee_no' => (string) $rec['employee_no'],
                            'event_time' => Carbon::parse($rec['event_time'])->format('Y-m-d H:i:s'),
                        ],
                        [
                            'employee_id' => $emp?->id,
                            'device_id' => $device?->id,
                            'card_no' => $rec['card_no'] ?? null,
                            'attendance_type' => $rec['attendance_type'] ?? 'auto',
                            'is_late' => (bool) ($rec['is_late'] ?? false),
                            'late_minutes' => (int) ($rec['late_minutes'] ?? 0),
                            'hikvision_event_id' => $rec['hikvision_event_id'] ?? null,
                            'raw_data' => $rec['raw_data'] ?? null,
                        ]
                    );
                }
            }

            // 6. Integrar Notas
            if (!empty($data['daily_notes'])) {
                foreach ($data['daily_notes'] as $note) {
                    $emp = Employee::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('employee_no', (string) $note['employee_no'])
                        ->first();

                    if ($emp) {
                        EmployeeDailyNote::updateOrCreate(
                            [
                                'user_id' => $userId,
                                'employee_id' => $emp->id,
                                'date' => Carbon::parse($note['date'])->format('Y-m-d'),
                            ],
                            [
                                'note' => $note['note'] ?? '',
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Información de la nube (empleados, horarios, departamentos) descargada correctamente.',
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::info('Modo sin conexión: No se pudo descargar de la nube central (' . $e->getMessage() . ')');
            return [
                'success' => false,
                'message' => 'Sin conexión con la nube. Se continúa usando la base de datos local.',
            ];
        } catch (\Throwable $t) {
            DB::rollBack();
            Log::error('Error en CloudSyncService::pull: ' . $t->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar los datos de la nube: ' . $t->getMessage(),
            ];
        }
    }

    /**
     * Sincronización bidireccional completa (Descargar de la nube y luego Subir lo local).
     */
    public function sync(): array
    {
        // 1. Descargar cambios de la nube
        $pullRes = $this->pull();

        // 2. Subir marcaciones y datos locales
        $pushRes = $this->push();

        if ($pullRes['success'] || $pushRes['success']) {
            $msg = [];
            if ($pullRes['success']) $msg[] = 'Descarga completada';
            if ($pushRes['success']) $msg[] = 'Subida a la nube completada';

            return [
                'success' => true,
                'message' => 'Sincronización con la nube completada: ' . implode(', ', $msg) . '.',
            ];
        }

        return [
            'success' => false,
            'message' => $pushRes['message'] ?: $pullRes['message'],
        ];
    }
}
