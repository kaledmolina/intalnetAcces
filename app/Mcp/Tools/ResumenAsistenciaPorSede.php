<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Resumen de asistencia agrupado por sede: presentes, tardanzas, ausencias y minutos de tardanza. Solo lectura.')]
class ResumenAsistenciaPorSede extends ReadOnlyAttendanceTool
{
    protected string $name = 'resumen_asistencia_por_sede';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $fecha = $validated['fecha'] ?? null;
        $desde = $validated['desde'] ?? null;
        $hasta = $validated['hasta'] ?? null;

        if ($fecha === null && ($desde === null || $hasta === null)) {
            throw ValidationException::withMessages([
                'fecha' => 'Indique "fecha" o el par "desde" y "hasta".',
            ]);
        }

        if ($fecha === null) {
            $days = Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) + 1;

            if ($days > 92) {
                throw ValidationException::withMessages([
                    'hasta' => 'El rango no puede superar los 92 días.',
                ]);
            }
        }

        return $this->respond($this->queries()->resumenAsistenciaPorSede($fecha, $desde, $hasta));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'fecha' => $schema->string()
                ->description('Fecha única en formato YYYY-MM-DD. Si se usa, no hace falta el rango.')
                ->nullable(),
            'desde' => $schema->string()
                ->description('Fecha inicial del rango en formato YYYY-MM-DD.')
                ->nullable(),
            'hasta' => $schema->string()
                ->description('Fecha final del rango en formato YYYY-MM-DD (máximo 92 días).')
                ->nullable(),
        ];
    }
}
