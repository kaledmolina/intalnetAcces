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

#[Description('Marcaciones de un empleado (id, documento o número de empleado) en un rango de fechas. Solo hora, tipo y sede.')]
class MarcacionesDeEmpleado extends ReadOnlyAttendanceTool
{
    protected string $name = 'marcaciones_de_empleado';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'empleado' => ['required', 'string', 'max:64'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $days = Carbon::parse($validated['desde'])->diffInDays(Carbon::parse($validated['hasta'])) + 1;

        if ($days > 92) {
            throw ValidationException::withMessages([
                'hasta' => 'El rango no puede superar los 92 días.',
            ]);
        }

        return $this->respond(
            $this->queries()->marcacionesDeEmpleado($validated['empleado'], $validated['desde'], $validated['hasta'])
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'empleado' => $schema->string()
                ->description('Id interno, número de documento o número de empleado.')
                ->required(),
            'desde' => $schema->string()
                ->description('Fecha inicial en formato YYYY-MM-DD.')
                ->required(),
            'hasta' => $schema->string()
                ->description('Fecha final en formato YYYY-MM-DD (máximo 92 días).')
                ->required(),
        ];
    }
}
