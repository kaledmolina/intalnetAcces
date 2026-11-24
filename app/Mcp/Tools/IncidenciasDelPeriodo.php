<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Resumen rápido de incidencias para los últimos 7 días o el mes en curso: qué empleados llegaron tarde y cuántas veces, y qué empleados no registraron salida (incluye días con una sola marcación), con las fechas de cada caso. Solo lectura.')]
class IncidenciasDelPeriodo extends ReadOnlyAttendanceTool
{
    protected string $name = 'incidencias_del_periodo';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'periodo' => ['nullable', Rule::in(['7_dias', 'mes'])],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        return $this->respond($this->queries()->incidenciasDelPeriodo(
            $validated['periodo'] ?? '7_dias',
            $validated['sede'] ?? null,
            $validated['departamento'] ?? null,
        ));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'periodo' => $schema->string()
                ->description('"7_dias" para los últimos 7 días (por defecto) o "mes" para el mes en curso hasta hoy.')
                ->enum(['7_dias', 'mes'])
                ->nullable(),
            'sede' => $schema->string()
                ->description('Código o nombre de sede. Si se omite, se usa la sede del token.')
                ->nullable(),
            'departamento' => $schema->string()
                ->description('Nombre exacto del departamento.')
                ->nullable(),
        ];
    }
}
