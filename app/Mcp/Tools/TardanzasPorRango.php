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

#[Description('Tardanzas entre dos fechas (máximo 92 días) con resumen por empleado. Solo lectura.')]
class TardanzasPorRango extends ReadOnlyAttendanceTool
{
    protected string $name = 'tardanzas_por_rango';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        $days = Carbon::parse($validated['desde'])->diffInDays(Carbon::parse($validated['hasta'])) + 1;

        if ($days > 92) {
            throw ValidationException::withMessages([
                'hasta' => 'El rango no puede superar los 92 días.',
            ]);
        }

        return $this->respond(
            $this->queries()->tardanzasPorRango(
                $validated['desde'],
                $validated['hasta'],
                $validated['sede'] ?? null,
                $validated['departamento'] ?? null,
            )
        );
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
                ->description('Código o nombre de sede.')
                ->nullable(),
            'departamento' => $schema->string()
                ->description('Nombre exacto del departamento.')
                ->nullable(),
        ];
    }
}
