<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class McpAttendanceToolsTest extends TestCase
{
    use RefreshDatabase;

    private const FECHA = '2026-11-20';

    private User $userA;

    private string $tokenA;

    private string $tokenB;

    private int $employeeAId;

    protected function setUp(): void
    {
        parent::setUp();

        $sedeA = Sede::create(['code' => 'SEDE-001', 'name' => 'Central']);
        $sedeB = Sede::create(['code' => 'SEDE-002', 'name' => 'Norte']);

        $this->userA = User::create([
            'name' => 'Admin Central',
            'email' => 'central@example.com',
            'password' => 'secret-password',
            'sede_id' => $sedeA->id,
            'sede' => $sedeA->name,
            'is_active' => true,
        ]);

        $userB = User::create([
            'name' => 'Admin Norte',
            'email' => 'norte@example.com',
            'password' => 'secret-password',
            'sede_id' => $sedeB->id,
            'sede' => $sedeB->name,
            'is_active' => true,
        ]);

        $department = Department::create([
            'user_id' => $this->userA->id,
            'name' => 'Operaciones',
        ]);

        $schedule = Schedule::create([
            'user_id' => $this->userA->id,
            'name' => 'Jornada',
            'tolerance_minutes' => 5,
            'is_default' => true,
        ]);

        ScheduleDay::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => 5,
            'is_working_day' => true,
            'entry_time' => '08:00:00',
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'exit_time' => '17:00:00',
        ]);

        $employee = Employee::create([
            'user_id' => $this->userA->id,
            'employee_no' => 'E-101',
            'name' => 'Ana Pérez',
            'document_id' => '123456',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->employeeAId = $employee->id;

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $employee->id,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 08:20:00',
            'attendance_type' => 'check_in',
            'is_late' => true,
            'late_minutes' => 20,
        ]);

        $this->tokenA = $this->userA->createToken('test-a', ['mcp:read'])->plainTextToken;
        $this->tokenB = $userB->createToken('test-b', ['mcp:read'])->plainTextToken;
    }

    public function test_las_herramientas_funcionan_con_el_token_correcto(): void
    {
        $tardanzas = $this->callTool($this->tokenA, 'tardanzas_del_dia', ['fecha' => self::FECHA]);

        $tardanzas->assertOk();
        $tardanzas->assertJsonPath('result.structuredContent.herramienta', 'tardanzas_del_dia');
        $tardanzas->assertJsonPath('result.structuredContent.total', 1);
        $tardanzas->assertJsonPath('result.structuredContent.data.0.empleado', 'Ana Pérez');
        $tardanzas->assertJsonPath('result.structuredContent.data.0.sede', 'SEDE-001');
        $tardanzas->assertJsonPath('result.structuredContent.data.0.departamento', 'Operaciones');
        $tardanzas->assertJsonPath('result.structuredContent.data.0.minutos_tardanza', 20);
        $tardanzas->assertJsonPath('result.structuredContent.data.0.justificada', false);

        $rango = $this->callTool($this->tokenA, 'tardanzas_por_rango', [
            'desde' => self::FECHA,
            'hasta' => self::FECHA,
            'sede' => 'SEDE-001',
            'departamento' => 'Operaciones',
        ]);

        $rango->assertOk();
        $rango->assertJsonPath('result.structuredContent.total', 1);
        $rango->assertJsonPath('result.structuredContent.resumen_por_empleado.0.total_tardanzas', 1);
        $rango->assertJsonPath('result.structuredContent.resumen_por_empleado.0.minutos_acumulados', 20);

        $resumen = $this->callTool($this->tokenA, 'resumen_asistencia_por_sede', ['fecha' => self::FECHA]);

        $resumen->assertOk();
        $resumen->assertJsonPath('result.structuredContent.total', 1);
        $resumen->assertJsonPath('result.structuredContent.data.0.sede', 'SEDE-001');
        $resumen->assertJsonPath('result.structuredContent.data.0.presentes', 1);
        $resumen->assertJsonPath('result.structuredContent.data.0.tardanzas', 1);
        $resumen->assertJsonPath('result.structuredContent.data.0.ausencias', 0);
        $resumen->assertJsonPath('result.structuredContent.data.0.minutos_tardanza', 20);

        $marcaciones = $this->callTool($this->tokenA, 'marcaciones_de_empleado', [
            'empleado' => '123456',
            'desde' => self::FECHA,
            'hasta' => self::FECHA,
        ]);

        $marcaciones->assertOk();
        $marcaciones->assertJsonPath('result.structuredContent.total', 1);
        $marcaciones->assertJsonPath('result.structuredContent.data.0.tipo', 'check_in');
        $marcaciones->assertJsonPath('result.structuredContent.data.0.sede', 'SEDE-001');
        $this->assertStringContainsString('2026-11-20T08:20:00', $marcaciones->json('result.structuredContent.data.0.hora'));

        $busqueda = $this->callTool($this->tokenA, 'buscar_empleado', ['texto' => 'Ana']);

        $busqueda->assertOk();
        $busqueda->assertJsonPath('result.structuredContent.total', 1);
        $busqueda->assertJsonPath('result.structuredContent.data.0.nombre', 'Ana Pérez');
        $busqueda->assertJsonPath('result.structuredContent.data.0.documento', '123456');
        $busqueda->assertJsonPath('result.structuredContent.data.0.departamento', 'Operaciones');
        $busqueda->assertJsonPath('result.structuredContent.data.0.sede', 'SEDE-001');

        $horario = $this->callTool($this->tokenA, 'horario_de_empleado', ['empleado' => (string) $this->employeeAId]);

        $horario->assertOk();
        $horario->assertJsonPath('result.structuredContent.data.0.jornada', 'Jornada');
        $horario->assertJsonPath('result.structuredContent.data.0.tolerancia_minutos', 5);
        $horario->assertJsonPath('result.structuredContent.data.0.intervalos.0.dia_semana', 5);
        $horario->assertJsonPath('result.structuredContent.data.0.intervalos.0.entrada', '08:00:00');
    }

    public function test_un_token_de_otra_empresa_recibe_resultados_vacios(): void
    {
        $llamadas = [
            ['tardanzas_del_dia', ['fecha' => self::FECHA]],
            ['tardanzas_por_rango', ['desde' => self::FECHA, 'hasta' => self::FECHA]],
            ['resumen_asistencia_por_sede', ['fecha' => self::FECHA]],
            ['marcaciones_de_empleado', ['empleado' => '123456', 'desde' => self::FECHA, 'hasta' => self::FECHA]],
            ['buscar_empleado', ['texto' => 'Ana']],
            ['horario_de_empleado', ['empleado' => (string) $this->employeeAId]],
        ];

        foreach ($llamadas as [$herramienta, $argumentos]) {
            $response = $this->callTool($this->tokenB, $herramienta, $argumentos);

            $response->assertOk();
            $response->assertJsonPath('result.structuredContent.total', 0);
            $response->assertJsonCount(0, 'result.structuredContent.data');
            $response->assertJsonMissing(['empleado' => 'Ana Pérez']);
            $response->assertJsonMissing(['sede' => 'SEDE-001']);
        }
    }

    public function test_una_sede_no_administrada_devuelve_lista_vacia_y_mensaje(): void
    {
        $response = $this->callTool($this->tokenA, 'tardanzas_del_dia', [
            'fecha' => self::FECHA,
            'sede' => 'SEDE-002',
        ]);

        $response->assertOk();
        $response->assertJsonPath('result.structuredContent.total', 0);
        $response->assertJsonCount(0, 'result.structuredContent.data');
        $response->assertJsonPath(
            'result.structuredContent.mensaje',
            'La sede solicitada no está disponible para este usuario.'
        );
    }

    public function test_rechaza_peticiones_sin_token_con_401(): void
    {
        $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertStatus(401);
    }

    private function callTool(string $token, string $tool, array $arguments): TestResponse
    {
        return $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $tool,
                'arguments' => $arguments,
            ],
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
    }
}
