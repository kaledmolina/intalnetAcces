<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Ranking de tardanzas por empleado o departamento en un periodo (máximo 92 días), con minutos acumulados y comparación contra el periodo anterior de igual duración. Solo lectura.')]
class RankingTardanzas extends ReadOnlyAttendanceTool
{
    protected string $name = 'ranking_tardanzas';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
            'agrupar' => ['nullable', Rule::in(['empleado', 'departamento'])],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->ensureMaxRange($validated['desde'], $validated['hasta']);

        return $this->respond($this->queries()->rankingTardanzas(
            $validated['desde'],
            $validated['hasta'],
            $validated['sede'] ?? null,
            $validated['departamento'] ?? null,
            $validated['agrupar'] ?? 'empleado',
            (int) ($validated['limite'] ?? 20),
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
            'agrupar' => $schema->string()
                ->description('Agrupar por "empleado" (por defecto) o por "departamento".')
                ->enum(['empleado', 'departamento'])
                ->nullable(),
            'limite' => $schema->integer()
                ->description('Número máximo de grupos a devolver, entre 1 y 100. Por defecto 20.')
                ->nullable(),
        ];
    }
}
