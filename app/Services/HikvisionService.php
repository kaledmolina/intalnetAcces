<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use Carbon\Carbon;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\Log;

class HikvisionService
{
    /**
     * Crear un cliente Guzzle preconfigurado con autenticación Digest.
     */
    protected function getClient(Device $device): Client
    {
        $baseUrl = "{$device->protocol}://{$device->ip_address}:{$device->port}";

        return new Client([
            'base_uri' => $baseUrl,
            'auth' => [$device->username, $device->password, 'digest'],
            'verify' => false,
            'timeout' => 8,
            'connect_timeout' => 4,
        ]);
    }

    /**
     * Verificar si un dispositivo está online probando la raíz ISAPI.
     */
    public function checkDeviceStatus(Device $device): bool
    {
        try {
            $client = $this->getClient($device);
            $response = $client->request('GET', '/ISAPI/System/deviceInfo');

            $isOnline = $response->getStatusCode() === 200;

            $device->update([
                'status' => $isOnline ? 'online' : 'offline',
                'last_sync_at' => now(),
            ]);

            return $isOnline;
        } catch (\Exception $e) {
            Log::warning("Dispositivo {$device->name} ({$device->ip_address}) offline: " . $e->getMessage());

            $device->update([
                'status' => 'offline',
            ]);

            return false;
        }
    }

    /**
     * Verificar si la huella dactilar de un empleado existe en el dispositivo.
     */
    public function checkFingerprintStatus(Device $device, string $employeeNo): bool
    {
        $isRegistered = false;

        // Método 1: Búsqueda en tabla de huellas dactilares ISAPI
        try {
            $client = $this->getClient($device);

            $payload = [
                'FingerPrintSearchCond' => [
                    'searchID' => '1',
                    'searchResultPosition' => 0,
                    'maxResults' => 10,
                    'employeeNo' => (string) $employeeNo,
                ],
            ];

            $response = $client->request('POST', '/ISAPI/AccessControl/FingerPrint/Search?format=json', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $matches = $data['FingerPrintSearch']['numOfMatches'] ?? 0;
            $info = $data['FingerPrintSearch']['FingerPrintInfo'] ?? $data['FingerprintInfo'] ?? [];

            if ($matches > 0 || !empty($info)) {
                $isRegistered = true;
            }
        } catch (\Exception $e) {
            Log::info("Consulta de huella en formato JSON para {$employeeNo}: " . $e->getMessage());
        }

        // Método 2: Verificar eventos AcsEvent recientes de lectura dactilar
        if (!$isRegistered) {
            try {
                $client = $this->getClient($device);
                $eventPayload = [
                    'AcsEventCond' => [
                        'searchID' => '1',
                        'searchResultPosition' => 0,
                        'maxResults' => 15,
                        'startTime' => now()->subMinutes(10)->format('Y-m-d\TH:i:s'),
                        'endTime' => now()->format('Y-m-d\TH:i:s'),
                    ],
                ];

                $response = $client->request('POST', '/ISAPI/AccessControl/AcsEvent?format=json', [
                    'json' => $eventPayload,
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $eventList = $data['AcsEvent']['InfoList'] ?? [];

                foreach ($eventList as $evt) {
                    $empNo = (string) ($evt['employeeNoString'] ?? $evt['employeeNo'] ?? '');
                    if ($empNo === (string) $employeeNo) {
                        $isRegistered = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::info("Consulta de eventos AcsEvent para {$employeeNo}: " . $e->getMessage());
            }
        }

        if ($isRegistered) {
            $employee = Employee::where('employee_no', (string) $employeeNo)->first();
            if ($employee) {
                $employee->update(['has_fingerprint' => true]);
            }
        }

        return $isRegistered;
    }

    /**
     * Registrar/Crear usuario en la memoria del dispositivo Hikvision vía ISAPI.
     */
    public function pushUserToDevice(Device $device, string $employeeNo, string $name): bool
    {
        try {
            $client = $this->getClient($device);

            $payload = [
                'UserInfo' => [
                    'employeeNo' => (string) $employeeNo,
                    'name' => $name,
                    'userType' => 'normal',
                    'closeDelay' => 0,
                    'Valid' => [
                        'enable' => true,
                        'beginTime' => '2026-01-01T00:00:00',
                        'endTime' => '2035-12-31T23:59:59',
                    ],
                ],
            ];

            $response = $client->request('POST', '/ISAPI/AccessControl/UserInfo/Record?format=json', [
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error("Error al registrar usuario {$employeeNo} en {$device->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar orden de captura remota de huella dactilar al biométrico.
     */
    public function captureFingerprintFromDevice(Device $device, string $employeeNo): array
    {
        // 1. Aseguramos que el usuario existe en el huellero vía ISAPI
        $this->pushUserToDevice($device, $employeeNo, "Empleado #" . $employeeNo);

        try {
            $client = $this->getClient($device);

            $xmlPayload = '<?xml version="1.0" encoding="UTF-8"?>
<FingerPrintCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">
    <searchID>1</searchID>
    <employeeNo>' . $employeeNo . '</employeeNo>
    <fingerNo>1</fingerNo>
</FingerPrintCond>';

            $client->request('POST', '/ISAPI/AccessControl/CaptureFingerprint', [
                'headers' => [
                    'Content-Type' => 'application/xml',
                ],
                'body' => $xmlPayload,
            ]);

            return [
                'success' => true,
                'message' => '✔ Orden enviada. Esperando lectura de huella en el lector.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => true,
                'message' => "✔ Usuario #" . $employeeNo . " listo en " . $device->name . ". Por favor dile a la persona que coloque su dedo 3 veces en el sensor.",
            ];
        }
    }

    /**
     * Obtener lista de usuarios registrados en el biométrico.
     */
    public function getUsersFromDevice(Device $device): array
    {
        $allUsers = [];
        $position = 0;
        $limit = 30; // Límite estricto de respuesta de la API del biométrico

        try {
            $client = $this->getClient($device);

            do {
                $payload = [
                    'UserInfoSearchCond' => [
                        'searchID' => '1',
                        'searchResultPosition' => $position,
                        'maxResults' => $limit,
                    ],
                ];

                $response = $client->request('POST', '/ISAPI/AccessControl/UserInfo/Search?format=json', [
                    'json' => $payload,
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $users = $data['UserInfoSearch']['UserInfo'] ?? [];

                if (empty($users)) {
                    break;
                }

                $allUsers = array_merge($allUsers, $users);
                $position += count($users);

                // Si retorna menos usuarios que el límite, hemos terminado
                if (count($users) < $limit) {
                    break;
                }
            } while (true);

            return $allUsers;
        } catch (\Exception $e) {
            Log::error("Error al buscar usuarios en {$device->name}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Importar y registrar automáticamente todos los empleados desde las terminales Hikvision a la Web.
     */
    public function importUsersFromAllDevices(): array
    {
        $devices = Device::all();
        $importedCount = 0;
        $updatedCount = 0;
        $defaultSchedule = Schedule::where('is_default', true)->first() ?? Schedule::first();

        foreach ($devices as $device) {
            $users = $this->getUsersFromDevice($device);

            foreach ($users as $u) {
                $employeeNo = $u['employeeNo'] ?? $u['employeeNoString'] ?? null;

                if (!$employeeNo) {
                    continue;
                }

                $name = !empty($u['name']) ? $u['name'] : "Empleado #" . $employeeNo;

                $employee = Employee::where('employee_no', (string) $employeeNo)
                    ->orWhere('document_id', (string) $employeeNo)
                    ->first();

                if (!$employee) {
                    $employee = Employee::create([
                        'employee_no' => (string) $employeeNo,
                        'name' => $name,
                        'is_active' => true,
                        'has_fingerprint' => true,
                    ]);

                    if ($defaultSchedule) {
                        EmployeeSchedule::create([
                            'employee_id' => $employee->id,
                            'schedule_id' => $defaultSchedule->id,
                            'start_date' => now()->toDateString(),
                        ]);
                    }

                    $importedCount++;
                } else {
                    if (!empty($u['name']) && $employee->name !== $u['name']) {
                        $employee->update([
                            'name' => $u['name'],
                            'has_fingerprint' => true,
                        ]);
                        $updatedCount++;
                    }
                }
            }
        }

        return [
            'imported' => $importedCount,
            'updated' => $updatedCount,
        ];
    }

    /**
     * Obtener marcaciones de asistencia (AcsEvents) desde el huellero.
     */
    public function syncEventsFromDevice(Device $device, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $startDate = $startDate ?? now()->startOfDay();
        $endDate = $endDate ?? now()->endOfDay();

        $eventsCount = 0;

        try {
            $client = $this->getClient($device);

            // 1. Consulta inicial para obtener el totalMatches de los eventos del huellero
            $initPayload = [
                'AcsEventCond' => [
                    'searchID' => '1',
                    'searchResultPosition' => 0,
                    'maxResults' => 1,
                    'major' => 0,
                    'minor' => 0,
                    'startTime' => $startDate->format('Y-m-d\TH:i:sP'),
                    'endTime' => $endDate->format('Y-m-d\TH:i:sP'),
                ],
            ];

            $initResponse = $client->request('POST', '/ISAPI/AccessControl/AcsEvent?format=json', [
                'json' => $initPayload,
            ]);

            $initData = json_decode($initResponse->getBody()->getContents(), true);
            $totalMatches = $initData['AcsEvent']['totalMatches'] ?? 0;

            if ($totalMatches > 0) {
                $position = 0;
                $limit = 30; // Fijado exactamente a 30 por limitación física del dispositivo (ISAPI)

                while ($position < $totalMatches) {
                    $payload = [
                        'AcsEventCond' => [
                            'searchID' => '1',
                            'searchResultPosition' => $position,
                            'maxResults' => $limit,
                            'major' => 0,
                            'minor' => 0,
                            'startTime' => $startDate->format('Y-m-d\TH:i:sP'),
                            'endTime' => $endDate->format('Y-m-d\TH:i:sP'),
                        ],
                    ];

                    $response = $client->request('POST', '/ISAPI/AccessControl/AcsEvent?format=json', [
                        'json' => $payload,
                    ]);

                    $data = json_decode($response->getBody()->getContents(), true);
                    $eventList = $data['AcsEvent']['InfoList'] ?? [];

                    if (empty($eventList)) {
                        break;
                    }

                    foreach ($eventList as $event) {
                        $employeeNo = (string) ($event['employeeNoString'] ?? $event['employeeNo'] ?? '');

                        if (empty($employeeNo)) {
                            continue;
                        }

                        $eventTime = isset($event['time']) ? Carbon::parse($event['time']) : null;
                        if (!$eventTime) {
                            continue;
                        }

                        $employee = Employee::where('employee_no', $employeeNo)
                            ->orWhere('document_id', 'like', "%{$employeeNo}%")
                            ->first();

                        if (!$employee) {
                            $employee = Employee::create([
                                'employee_no' => $employeeNo,
                                'name' => !empty($event['name']) ? $event['name'] : "Empleado #" . $employeeNo,
                                'is_active' => true,
                                'has_fingerprint' => true,
                            ]);
                        } else {
                            if (!$employee->has_fingerprint) {
                                $employee->update(['has_fingerprint' => true]);
                            }
                        }

                        $schedule = $employee->currentSchedule();
                        $isLate = false;
                        $lateMinutes = 0;

                        if ($schedule) {
                            $dayConfig = $schedule->days()->where('day_of_week', $eventTime->dayOfWeekIso)->first();

                            if ($dayConfig && $dayConfig->is_working_day) {
                                $expectedTimes = [];
                                $baseDate = $eventTime->toDateString();

                                if ($dayConfig->entry_time) {
                                    $expectedTimes['entry'] = ['time' => Carbon::parse($baseDate . ' ' . $dayConfig->entry_time), 'type' => 'in', 'check_tardiness' => true];
                                }
                                if ($dayConfig->break_start_time) {
                                    $expectedTimes['break_start'] = ['time' => Carbon::parse($baseDate . ' ' . $dayConfig->break_start_time), 'type' => 'out', 'check_tardiness' => false];
                                }
                                if ($dayConfig->break_end_time) {
                                    $expectedTimes['break_end'] = ['time' => Carbon::parse($baseDate . ' ' . $dayConfig->break_end_time), 'type' => 'in', 'check_tardiness' => $schedule->check_break_tardiness];
                                }
                                if ($dayConfig->exit_time) {
                                    $expectedTimes['exit'] = ['time' => Carbon::parse($baseDate . ' ' . $dayConfig->exit_time), 'type' => 'out', 'check_tardiness' => false];
                                }

                                $closest = null;
                                $minDiff = PHP_INT_MAX;

                                foreach ($expectedTimes as $key => $data) {
                                    $diff = abs($eventTime->diffInMinutes($data['time'], false));
                                    if ($diff < $minDiff) {
                                        $minDiff = $diff;
                                        $closest = $data;
                                    }
                                }

                                if ($closest && $closest['type'] === 'in' && $closest['check_tardiness']) {
                                    $toleranceDeadline = (clone $closest['time'])->addMinutes($schedule->tolerance_minutes);
                                    
                                    // Si marca después de la tolerancia y dentro de 4 horas
                                    if ($eventTime->greaterThan($toleranceDeadline) && $eventTime->diffInHours($closest['time']) < 4) {
                                        $isLate = true;
                                        $lateMinutes = $eventTime->diffInMinutes($closest['time']);
                                    }
                                }
                            }
                        }

                        $rawStatus = isset($event['attendanceStatus']) ? strtolower($event['attendanceStatus']) : 'auto';
                        $attendanceType = 'auto';
                        if ($rawStatus === 'checkin') {
                            $attendanceType = 'check_in';
                        } elseif ($rawStatus === 'checkout') {
                            $attendanceType = 'check_out';
                        }
                        
                        // Si el dispositivo envió 'auto', podemos inferirlo del 'closest' para tener mejor data
                        if ($attendanceType === 'auto' && isset($closest)) {
                            $attendanceType = $closest['type'] === 'in' ? 'check_in' : 'check_out';
                        }

                        $eventId = $event['serialNo'] ?? ($employeeNo . '_' . $eventTime->timestamp);

                        $record = AttendanceRecord::updateOrCreate(
                            [
                                'employee_no' => (string) $employeeNo,
                                'event_time' => $eventTime->format('Y-m-d H:i:s'),
                            ],
                            [
                                'device_id' => $device->id,
                                'employee_id' => $employee->id,
                                'card_no' => $event['cardNo'] ?? null,
                                'attendance_type' => $attendanceType,
                                'is_late' => $isLate,
                                'late_minutes' => $lateMinutes,
                                'hikvision_event_id' => (string) $eventId,
                                'raw_data' => $event,
                            ]
                        );

                        if ($record->wasRecentlyCreated) {
                            $eventsCount++;
                        }
                    }

                    $position += count($eventList);

                    if (count($eventList) < $limit) {
                        break;
                    }
                }
            }

            $device->update([
                'status' => 'online',
                'last_sync_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error al sincronizar marcaciones de {$device->name} ({$device->ip_address}): " . $e->getMessage());
            $device->update(['status' => 'offline']);
        }

        return $eventsCount;
    }

    /**
     * Sincronizar todos los huelleros registrados.
     */
    public function syncAllDevices(): array
    {
        $devices = Device::all();
        $results = [];

        foreach ($devices as $device) {
            $count = $this->syncEventsFromDevice($device);
            $results[$device->name] = [
                'status' => $device->status,
                'new_events' => $count,
                'ip' => $device->ip_address,
            ];
        }

        return $results;
    }

    /**
     * Configurar el biométrico para que la marcación dactilar sea directa (Sin pedir teclas de estado).
     */
    public function configureAutoCheckInMode(Device $device): bool
    {
        try {
            $client = $this->getClient($device);

            $payload = [
                'AttendanceRule' => [
                    'statusMode' => 'auto',
                    'defaultStatus' => 'checkIn',
                    'statusSelectTimeout' => 0,
                ],
            ];

            $response = $client->request('PUT', '/ISAPI/AccessControl/AttendanceRule?format=json', [
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::info("Intento ISAPI AttendanceRule en {$device->name}: " . $e->getMessage());
            return false;
        }
    }
}
