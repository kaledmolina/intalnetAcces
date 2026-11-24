<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Genera un CSV (delimitador ";") de tardanzas, ausencias o jornada real para un periodo de hasta 92 días, con máximo 1000 filas. Devuelve el contenido CSV listo para guardar o adjuntar. Solo lectura.')]
class ExportarReporte extends ReadOnlyAttendanceTool
{
    protected string $name = 'exportar_reporte';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'tipo' => ['nullable', Rule::in(['tardanzas', 'ausencias', 'horas_trabajadas'])],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        $this->ensureMaxRange($validated['desde'], $validated['hasta']);

        return $this->respond($this->queries()->exportarReporte(
            $validated['tipo'] ?? 'tardanzas',
            $validated['desde'],
            $validated['hasta'],
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
            'tipo' => $schema->string()
                ->description('Contenido del reporte: "tardanzas" (por defecto), "ausencias" u "horas_trabajadas".')
                ->enum(['tardanzas', 'ausencias', 'horas_trabajadas'])
                ->nullable(),
            'desde' => $schema->string()
                ->description('Fecha inicial en formato YYYY-MM-DD.')
                ->required(),
            'hasta' => $schema->string()
                ->description('Fecha final en formato YYYY-MM-DD (máximo 92 días desde "desde").')
                ->required(),
            'sede' => $schema->string()
                ->description('Código o nombre de sede. Si se omite, se usa la sede del token.')
                ->nullable(),
            'departamento' => $schema->string()
                ->description('Nombre exacto del departamento.')
                ->nullable(),
        ];
    }
}
