<?php

namespace Tests\Unit;

use App\Models\ScheduleDay;
use App\Support\EarlyExitRule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EarlyExitRuleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function day(array $attributes = []): ScheduleDay
    {
        return new ScheduleDay(array_merge([
            'day_of_week' => 5,
            'is_working_day' => true,
            'entry_time' => '08:00:00',
            'exit_time' => '17:00:00',
        ], $attributes));
    }

    public function test_detecta_la_salida_temprana_fuera_de_tolerancia(): void
    {
        $result = EarlyExitRule::detect(
            Carbon::parse('2026-11-20 08:20:00'),
            Carbon::parse('2026-11-20 15:30:00'),
            $this->day(),
            5,
            Carbon::parse('2026-11-26 10:00:00'),
            'check_out',
        );

        $this->assertNotNull($result);
        $this->assertSame(90, $result['minutes']);
        $this->assertSame('2026-11-20 17:00:00', $result['scheduled_exit']->format('Y-m-d H:i:s'));
    }

    public function test_no_marca_salida_temprana_dentro_de_la_tolerancia(): void
    {
        $this->assertNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-20 08:00:00'),
            Carbon::parse('2026-11-20 16:57:00'),
            $this->day(),
            5,
            Carbon::parse('2026-11-26 10:00:00'),
            'check_out',
        ));
    }

    public function test_no_evalua_el_turno_de_hoy_si_sigue_en_curso(): void
    {
        $now = Carbon::parse('2026-11-26 10:00:00');

        $this->assertNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-26 08:00:00'),
            Carbon::parse('2026-11-26 09:30:00'),
            $this->day(),
            5,
            $now,
            'auto',
        ));

        $this->assertNotNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-26 08:00:00'),
            Carbon::parse('2026-11-26 09:30:00'),
            $this->day(),
            5,
            $now,
            'check_out',
        ));
    }

    public function test_ignora_jornadas_demasiado_cortas(): void
    {
        $this->assertNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-20 08:00:00'),
            Carbon::parse('2026-11-20 08:20:00'),
            $this->day(),
            5,
            Carbon::parse('2026-11-26 10:00:00'),
            'check_out',
        ));
    }

    public function test_ignora_dias_no_laborables_o_sin_horario_de_salida(): void
    {
        $this->assertNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-22 08:00:00'),
            Carbon::parse('2026-11-22 12:00:00'),
            $this->day(['is_working_day' => false]),
            5,
            Carbon::parse('2026-11-26 10:00:00'),
            'check_out',
        ));

        $this->assertNull(EarlyExitRule::detect(
            Carbon::parse('2026-11-20 08:00:00'),
            Carbon::parse('2026-11-20 12:00:00'),
            null,
            5,
            Carbon::parse('2026-11-26 10:00:00'),
            'check_out',
        ));
    }
}
