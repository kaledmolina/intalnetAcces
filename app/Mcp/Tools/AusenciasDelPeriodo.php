<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Ausencias por empleado y día en un periodo (máximo 92 días), contando solo días laborables del horario vigente y excluyendo fechas futuras. Indica si cada ausencia está justificada con una nota. Solo lectura.')]
class AusenciasDelPeriodo extends ReadOnlyAttendanceTool
{
    protected string $name = 'ausencias_del_periodo';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        $this->ensureMaxRange($validated['desde'], $validated['hasta']);

        return $this->respond($this->queries()->ausenciasDelPeriodo(
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
