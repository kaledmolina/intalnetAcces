<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDailyNote;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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
            ['entradas_del_dia', ['fecha' => self::FECHA]],
            ['salidas_del_dia', ['fecha' => self::FECHA]],
            ['tardanzas_por_rango', ['desde' => self::FECHA, 'hasta' => self::FECHA]],
            ['resumen_asistencia_por_sede', ['fecha' => self::FECHA]],
            ['marcaciones_de_empleado', ['empleado' => '123456', 'desde' => self::FECHA, 'hasta' => self::FECHA]],
            ['buscar_empleado', ['texto' => 'Ana']],
            ['horario_de_empleado', ['empleado' => (string) $this->employeeAId]],
            ['detalle_de_empleado', ['empleado' => (string) $this->employeeAId]],
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

    public function test_entradas_del_dia_ordena_por_hora_y_identifica_al_primero(): void
    {
        $department = Department::where('name', 'Operaciones')->firstOrFail();

        $employeeB = Employee::create([
            'user_id' => $this->userA->id,
            'employee_no' => 'E-102',
            'name' => 'Beto Gómez',
            'document_id' => '654321',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->userA->id,
            'employee_no' => 'E-103',
            'name' => 'Carla Ruiz',
            'document_id' => '777777',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $employeeB->id,
            'employee_no' => 'E-102',
            'event_time' => self::FECHA.' 07:45:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        $response = $this->callTool($this->tokenA, 'entradas_del_dia', ['fecha' => self::FECHA]);

        $response->assertOk();
        $response->assertJsonPath('result.structuredContent.total', 2);
        $response->assertJsonPath('result.structuredContent.data.0.empleado', 'Beto Gómez');
        $response->assertJsonPath('result.structuredContent.data.0.puesto', 1);
        $response->assertJsonPath('result.structuredContent.data.0.es_primero', true);
        $response->assertJsonPath('result.structuredContent.data.0.hora', '07:45:00');
        $response->assertJsonPath('result.structuredContent.data.0.horario_entrada', '08:00:00');
        $response->assertJsonPath('result.structuredContent.data.0.minutos_diferencia', -15);
        $response->assertJsonPath('result.structuredContent.data.0.puntual', true);
        $response->assertJsonPath('result.structuredContent.data.1.empleado', 'Ana Pérez');
        $response->assertJsonPath('result.structuredContent.data.1.puesto', 2);
        $response->assertJsonPath('result.structuredContent.data.1.minutos_diferencia', 20);
        $response->assertJsonPath('result.structuredContent.data.1.puntual', false);
        $response->assertJsonPath('result.structuredContent.resumen.primero.empleado', 'Beto Gómez');
        $response->assertJsonPath('result.structuredContent.resumen.primero.hora', '07:45:00');
        $response->assertJsonPath('result.structuredContent.resumen.empleados_considerados', 3);
        $response->assertJsonPath('result.structuredContent.resumen.con_marcacion', 2);
        $response->assertJsonPath('result.structuredContent.resumen.sin_marcacion', 1);
        $response->assertJsonCount(1, 'result.structuredContent.empleados_sin_marcacion');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.empleado', 'Carla Ruiz');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.documento', '777777');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.sede', 'SEDE-001');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.departamento', 'Operaciones');
    }

    public function test_salidas_del_dia_usa_la_ultima_marcacion_y_lista_quien_no_marco_salida(): void
    {
        $department = Department::where('name', 'Operaciones')->firstOrFail();

        $employeeB = Employee::create([
            'user_id' => $this->userA->id,
            'employee_no' => 'E-102',
            'name' => 'Beto Gómez',
            'document_id' => '654321',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->userA->id,
            'employee_no' => 'E-103',
            'name' => 'Carla Ruiz',
            'document_id' => '777777',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $employeeB->id,
            'employee_no' => 'E-102',
            'event_time' => self::FECHA.' 07:45:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 17:10:00',
            'attendance_type' => 'check_out',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        $response = $this->callTool($this->tokenA, 'salidas_del_dia', ['fecha' => self::FECHA]);

        $response->assertOk();
        $response->assertJsonPath('result.structuredContent.herramienta', 'salidas_del_dia');
        $response->assertJsonPath('result.structuredContent.total', 2);
        $response->assertJsonPath('result.structuredContent.data.0.empleado', 'Beto Gómez');
        $response->assertJsonPath('result.structuredContent.data.0.hora_ultima_marcacion', '07:45:00');
        $response->assertJsonPath('result.structuredContent.data.0.tipo_ultima_marcacion', 'check_in');
        $response->assertJsonPath('result.structuredContent.data.0.marco_salida', false);
        $response->assertJsonPath('result.structuredContent.data.0.hora_salida', null);
        $response->assertJsonPath('result.structuredContent.data.1.empleado', 'Ana Pérez');
        $response->assertJsonPath('result.structuredContent.data.1.hora_ultima_marcacion', '17:10:00');
        $response->assertJsonPath('result.structuredContent.data.1.marco_salida', true);
        $response->assertJsonPath('result.structuredContent.data.1.hora_salida', '17:10:00');
        $response->assertJsonPath('result.structuredContent.data.1.horario_salida', '17:00:00');
        $response->assertJsonPath('result.structuredContent.data.1.minutos_diferencia', 10);
        $response->assertJsonPath('result.structuredContent.resumen.empleados_considerados', 3);
        $response->assertJsonPath('result.structuredContent.resumen.sin_salida', 1);
        $response->assertJsonPath('result.structuredContent.resumen.sin_marcacion', 1);
        $response->assertJsonPath('result.structuredContent.resumen.ultimo.empleado', 'Ana Pérez');
        $response->assertJsonCount(1, 'result.structuredContent.empleados_sin_salida');
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.empleado', 'Beto Gómez');
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.sede', 'SEDE-001');
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.departamento', 'Operaciones');
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.hora_ultima_marcacion', '07:45:00');
        $response->assertJsonCount(1, 'result.structuredContent.empleados_sin_marcacion');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.empleado', 'Carla Ruiz');
        $response->assertJsonPath('result.structuredContent.empleados_sin_marcacion.0.departamento', 'Operaciones');
    }

    public function test_salidas_del_dia_no_da_por_salida_una_entrada_posterior_al_check_out(): void
    {
        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 17:05:00',
            'attendance_type' => 'check_out',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 18:30:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        $response = $this->callTool($this->tokenA, 'salidas_del_dia', ['fecha' => self::FECHA]);

        $response->assertOk();
        $response->assertJsonPath('result.structuredContent.total', 1);
        $response->assertJsonPath('result.structuredContent.data.0.hora_ultima_marcacion', '18:30:00');
        $response->assertJsonPath('result.structuredContent.data.0.tipo_ultima_marcacion', 'check_in');
        $response->assertJsonPath('result.structuredContent.data.0.marco_salida', false);
        $response->assertJsonPath('result.structuredContent.data.0.hora_salida', null);
        $response->assertJsonPath('result.structuredContent.resumen.sin_salida', 1);
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.empleado', 'Ana Pérez');
        $response->assertJsonPath('result.structuredContent.empleados_sin_salida.0.hora_ultima_marcacion', '18:30:00');
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

    public function test_no_se_puede_resolver_un_empleado_ajeno_por_documento(): void
    {
        $response = $this->callTool($this->tokenB, 'detalle_de_empleado', ['empleado' => '123456']);

        $response->assertOk();
        $response->assertJsonPath('result.structuredContent.total', 0);
        $response->assertJsonMissing(['empleado' => 'Ana Pérez']);
    }

    public function test_herramientas_analiticas(): void
    {
        $schedule = Schedule::where('is_default', true)->firstOrFail();

        foreach ([1, 2] as $dayOfWeek) {
            ScheduleDay::create([
                'schedule_id' => $schedule->id,
                'day_of_week' => $dayOfWeek,
                'is_working_day' => true,
                'entry_time' => '08:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'exit_time' => '17:00:00',
            ]);
        }

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 17:10:00',
            'attendance_type' => 'check_out',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => '2026-11-16 08:05:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        EmployeeDailyNote::create([
            'employee_id' => $this->employeeAId,
            'date' => '2026-11-17',
            'note' => 'Permiso personal',
            'user_id' => $this->userA->id,
        ]);

        $catalogo = $this->callTool($this->tokenA, 'catalogo_sedes_departamentos', []);
        $catalogo->assertOk();
        $catalogo->assertJsonPath('result.structuredContent.total', 2);
        $catalogo->assertJsonFragment(['tipo' => 'sede', 'codigo' => 'SEDE-001']);
        $catalogo->assertJsonFragment(['tipo' => 'departamento', 'nombre' => 'Operaciones', 'empleados_activos' => 1]);

        $ranking = $this->callTool($this->tokenA, 'ranking_tardanzas', [
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $ranking->assertOk();
        $ranking->assertJsonPath('result.structuredContent.total', 1);
        $ranking->assertJsonPath('result.structuredContent.data.0.empleado', 'Ana Pérez');
        $ranking->assertJsonPath('result.structuredContent.data.0.tardanzas', 1);
        $ranking->assertJsonPath('result.structuredContent.data.0.minutos_tardanza', 20);
        $ranking->assertJsonPath('result.structuredContent.data.0.variacion_tardanzas', 1);

        $ausencias = $this->callTool($this->tokenA, 'ausencias_del_periodo', [
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $ausencias->assertOk();
        $ausencias->assertJsonPath('result.structuredContent.total', 1);
        $ausencias->assertJsonPath('result.structuredContent.data.0.fecha', '2026-11-17');
        $ausencias->assertJsonPath('result.structuredContent.data.0.dia_semana', 'martes');
        $ausencias->assertJsonPath('result.structuredContent.data.0.justificada', true);
        $ausencias->assertJsonPath('result.structuredContent.data.0.nota', 'Permiso personal');

        $incompletas = $this->callTool($this->tokenA, 'marcaciones_incompletas', [
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $incompletas->assertOk();
        $incompletas->assertJsonPath('result.structuredContent.total', 1);
        $incompletas->assertJsonPath('result.structuredContent.data.0.fecha', '2026-11-16');
        $incompletas->assertJsonPath('result.structuredContent.data.0.problema', 'marcacion_unica');

        $horas = $this->callTool($this->tokenA, 'horas_trabajadas', [
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $horas->assertOk();
        $horas->assertJsonPath('result.structuredContent.data.0.minutos_trabajados', 470);
        $horas->assertJsonPath('result.structuredContent.data.0.minutos_programados', 1440);
        $horas->assertJsonPath('result.structuredContent.data.0.dias_sin_marcacion', 1);
        $horas->assertJsonPath('result.structuredContent.data.0.dias_incompletos', 1);
        $horas->assertJsonPath('result.structuredContent.data.0.diferencia_minutos', -970);

        $detalle = $this->callTool($this->tokenA, 'detalle_de_empleado', ['empleado' => '123456']);
        $detalle->assertOk();
        $detalle->assertJsonPath('result.structuredContent.data.0.empleado', 'Ana Pérez');
        $detalle->assertJsonPath('result.structuredContent.data.0.horario.jornada', 'Jornada');
        $detalle->assertJsonPath('result.structuredContent.data.0.ultima_marcacion.tipo', 'check_out');
        $detalle->assertJsonPath('result.structuredContent.data.0.tardanzas_mes_actual.total', 1);
        $detalle->assertJsonPath('result.structuredContent.data.0.tardanzas_mes_actual.justificadas', 0);
        $detalle->assertJsonPath('result.structuredContent.data.0.notas_recientes.0.nota', 'Permiso personal');

        $export = $this->callTool($this->tokenA, 'exportar_reporte', [
            'tipo' => 'tardanzas',
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $export->assertOk();
        $export->assertJsonPath('result.structuredContent.formato', 'csv');
        $export->assertJsonPath('result.structuredContent.filas', 1);
        $this->assertStringContainsString('Ana Pérez', $export->json('result.structuredContent.csv'));
        $this->assertStringContainsString('2026-11-20', $export->json('result.structuredContent.csv'));

        $exportAusencias = $this->callTool($this->tokenA, 'exportar_reporte', [
            'tipo' => 'ausencias',
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $exportAusencias->assertOk();
        $this->assertStringContainsString('Permiso personal', $exportAusencias->json('result.structuredContent.csv'));
    }

    public function test_incidencias_del_periodo(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $schedule = Schedule::where('is_default', true)->firstOrFail();

        ScheduleDay::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => 1,
            'is_working_day' => true,
            'entry_time' => '08:00:00',
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'exit_time' => '17:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 17:10:00',
            'attendance_type' => 'check_out',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => '2026-11-16 08:05:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        $semana = $this->callTool($this->tokenA, 'incidencias_del_periodo', ['periodo' => '7_dias']);

        $semana->assertOk();
        $semana->assertJsonPath('result.structuredContent.periodo.desde', '2026-11-18');
        $semana->assertJsonPath('result.structuredContent.periodo.hasta', '2026-11-24');
        $semana->assertJsonPath('result.structuredContent.tardanzas.total_empleados', 1);
        $semana->assertJsonPath('result.structuredContent.tardanzas.total_veces', 1);
        $semana->assertJsonPath('result.structuredContent.tardanzas.data.0.empleado', 'Ana Pérez');
        $semana->assertJsonPath('result.structuredContent.tardanzas.data.0.veces', 1);
        $semana->assertJsonPath('result.structuredContent.sin_salida.total_empleados', 0);

        $mes = $this->callTool($this->tokenA, 'incidencias_del_periodo', ['periodo' => 'mes']);

        $mes->assertOk();
        $mes->assertJsonPath('result.structuredContent.periodo.desde', '2026-11-01');
        $mes->assertJsonPath('result.structuredContent.tardanzas.total_veces', 1);
        $mes->assertJsonPath('result.structuredContent.sin_salida.total_empleados', 1);
        $mes->assertJsonPath('result.structuredContent.sin_salida.total_veces', 1);
        $mes->assertJsonPath('result.structuredContent.sin_salida.data.0.empleado', 'Ana Pérez');
        $mes->assertJsonPath('result.structuredContent.sin_salida.data.0.dias.0.fecha', '2026-11-16');
    }

    public function test_exportar_reporte_incluye_marcaciones_incompletas_novedades_y_sin_asignar(): void
    {
        Carbon::setTestNow('2026-11-26 10:00:00');

        // Viernes 2026-11-20: entrada 08:20 (tarde) y salida 15:30 (90 min antes del horario).
        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => self::FECHA.' 15:30:00',
            'attendance_type' => 'check_out',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        // Jueves 2026-11-19: una sola marcación.
        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => $this->employeeAId,
            'employee_no' => 'E-101',
            'event_time' => '2026-11-19 08:00:00',
            'attendance_type' => 'check_in',
            'is_late' => false,
            'late_minutes' => 0,
        ]);

        // Marcación que llegó del huellero sin empleado asociado.
        AttendanceRecord::create([
            'user_id' => $this->userA->id,
            'employee_id' => null,
            'employee_no' => '999',
            'card_no' => 'CARD-9',
            'event_time' => self::FECHA.' 09:05:00',
            'attendance_type' => 'auto',
        ]);

        $incompletas = $this->callTool($this->tokenA, 'exportar_reporte', [
            'tipo' => 'marcaciones_incompletas',
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $incompletas->assertOk();
        $incompletas->assertJsonPath('result.structuredContent.tipo', 'marcaciones_incompletas');
        $incompletas->assertJsonPath('result.structuredContent.filas', 1);

        $csvIncompletas = $incompletas->json('result.structuredContent.csv');
        $this->assertStringContainsString('marcacion_unica', $csvIncompletas);
        $this->assertStringContainsString('2026-11-19', $csvIncompletas);

        $novedades = $this->callTool($this->tokenA, 'exportar_reporte', [
            'tipo' => 'novedades',
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $novedades->assertOk();
        $novedades->assertJsonPath('result.structuredContent.filas', 3);

        $csvNovedades = $novedades->json('result.structuredContent.csv');
        $this->assertStringContainsString('Llegada tarde', $csvNovedades);
        $this->assertStringContainsString('Salida temprana', $csvNovedades);
        $this->assertStringContainsString('Marcación única', $csvNovedades);
        $this->assertStringContainsString('90', $csvNovedades);

        $sinAsignar = $this->callTool($this->tokenA, 'exportar_reporte', [
            'tipo' => 'sin_asignar',
            'desde' => '2026-11-16',
            'hasta' => self::FECHA,
        ]);
        $sinAsignar->assertOk();
        $sinAsignar->assertJsonPath('result.structuredContent.filas', 1);

        $csvSinAsignar = $sinAsignar->json('result.structuredContent.csv');
        $this->assertStringContainsString('999', $csvSinAsignar);
        $this->assertStringContainsString('CARD-9', $csvSinAsignar);
    }

    public function test_prompts_y_recursos_del_servidor(): void
    {
        $prompts = $this->callMethod($this->tokenA, 'prompts/list');
        $prompts->assertOk();

        $nombres = collect($prompts->json('result.prompts'))->pluck('name')->all();
        $this->assertContains('informe_semanal', $nombres);
        $this->assertContains('resumen_del_dia', $nombres);

        $informe = $this->callMethod($this->tokenA, 'prompts/get', [
            'name' => 'informe_semanal',
            'arguments' => [
                'desde' => '2026-11-16',
                'hasta' => '2026-11-22',
            ],
        ]);
        $informe->assertOk();

        $texto = $informe->json('result.messages.0.content.text');
        $this->assertStringContainsString('2026-11-16', $texto);
        $this->assertStringContainsString('2026-11-22', $texto);
        $this->assertStringContainsString('marcaciones_incompletas', $texto);
        $this->assertStringContainsString('tardanzas_por_rango', $texto);

        $resumen = $this->callMethod($this->tokenA, 'prompts/get', [
            'name' => 'resumen_del_dia',
            'arguments' => ['fecha' => '2026-11-20'],
        ]);
        $resumen->assertOk();
        $this->assertStringContainsString('2026-11-20', $resumen->json('result.messages.0.content.text'));

        $recursos = $this->callMethod($this->tokenA, 'resources/list');
        $recursos->assertOk();

        $uris = collect($recursos->json('result.resources'))->pluck('uri')->all();
        $this->assertContains('intalnet://docs/diccionario-de-campos', $uris);

        $lectura = $this->callMethod($this->tokenA, 'resources/read', [
            'uri' => 'intalnet://docs/diccionario-de-campos',
        ]);
        $lectura->assertOk();

        $contenido = $lectura->json('result.contents.0.text');
        $this->assertStringContainsString('check_in', $contenido);
        $this->assertStringContainsString('sin_salida', $contenido);
        $this->assertStringContainsString('truncated', $contenido);
    }

    private function callMethod(string $token, string $method, array $params = []): TestResponse
    {
        return $this->postJson('/mcp/attendance', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
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
