<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Primeras marcaciones del día de todos los empleados, ordenadas de la más temprana a la más tardía. Devuelve la hora de entrada de cada uno, su horario programado, la diferencia en minutos (negativa si llegó antes) y un resumen con quién llegó primero, quién llegó último y cuántos no marcaron. Úsala para responder "quién llegó primero", "a qué hora entró cada empleado" o "cuántos llegaron antes de su horario". Solo lectura.')]
class EntradasDelDia extends ReadOnlyAttendanceTool
{
    protected string $name = 'entradas_del_dia';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return $this->respond($this->queries()->entradasDelDia(
            $validated['fecha'] ?? now()->toDateString(),
            $validated['sede'] ?? null,
            $validated['departamento'] ?? null,
            isset($validated['limite']) ? (int) $validated['limite'] : null,
        ));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'fecha' => $schema->string()
                ->description('Fecha en formato YYYY-MM-DD. Por defecto, hoy.')
                ->nullable(),
            'sede' => $schema->string()
                ->description('Código o nombre de sede. Si se omite, se usa la sede del token.')
                ->nullable(),
            'departamento' => $schema->string()
                ->description('Nombre exacto del departamento.')
                ->nullable(),
            'limite' => $schema->integer()
                ->description('Número máximo de empleados a devolver, entre 1 y 200. Por defecto 200.')
                ->nullable(),
        ];
    }
}
