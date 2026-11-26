<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Última marcación del día de todos los empleados, ordenadas de la más temprana a la más tardía. Devuelve la hora de la última marcación, si esa última marcación es una salida (check_out), su horario de salida y la diferencia en minutos (positiva si salió después). Incluye un resumen y las listas empleados_sin_salida (con marcación pero sin salida) y empleados_sin_marcacion (no marcaron nada), con nombre, sede y departamento. Úsala para responder "quién sigue en la empresa", "quién no ha marcado salida hoy", "a qué hora salió cada empleado" o "quién salió último". Solo lectura.')]
class SalidasDelDia extends ReadOnlyAttendanceTool
{
    protected string $name = 'salidas_del_dia';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return $this->respond($this->queries()->salidasDelDia(
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
