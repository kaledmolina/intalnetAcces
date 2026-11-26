<?php

namespace App\Support;

use App\Models\ScheduleDay;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Regla única de salida temprana.
 *
 * La usan el dashboard, la vista/exportación de Novedades y el CSV
 * `novedades` del MCP para que las tres superficies no se separen:
 * última marcación antes del horario de salida menos la tolerancia,
 * con al menos 25 minutos de jornada y nunca mientras el turno de hoy
 * siga en curso (salvo que el huellero haya marcado check_out).
 */
final class EarlyExitRule
{
    /**
     * Jornada mínima (minutos) para considerar la última marcación como salida
     * y no como un doble toque al entrar.
     */
    public const MIN_WORKED_MINUTES = 25;

    /**
     * @return array{scheduled_exit: Carbon, minutes: int}|null
     */
    public static function detect(
        CarbonInterface $firstPunch,
        CarbonInterface $lastPunch,
        ?ScheduleDay $day,
        int $toleranceMinutes,
        ?CarbonInterface $now = null,
        ?string $lastPunchType = null,
    ): ?array {
        if (! $day || ! $day->is_working_day || ! $day->exit_time) {
            return null;
        }

        if (abs((int) $firstPunch->diffInMinutes($lastPunch)) < self::MIN_WORKED_MINUTES) {
            return null;
        }

        $scheduledExit = Carbon::parse($lastPunch->toDateString().' '.$day->exit_time);
        $now ??= Carbon::now();
        $tolerance = max(0, $toleranceMinutes);

        // Con el turno de hoy todavía en curso no hay salida que evaluar,
        // salvo que el huellero haya registrado una salida explícita.
        if ($lastPunch->isSameDay($now) && $now->lessThan($scheduledExit) && $lastPunchType !== 'check_out') {
            return null;
        }

        if ($lastPunch->greaterThanOrEqualTo($scheduledExit->copy()->subMinutes($tolerance))) {
            return null;
        }

        return [
            'scheduled_exit' => $scheduledExit,
            'minutes' => abs((int) $scheduledExit->diffInMinutes($lastPunch)),
        ];
    }
}
