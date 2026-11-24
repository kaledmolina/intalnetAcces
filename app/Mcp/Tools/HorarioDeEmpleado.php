<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Horario vigente de un empleado: jornada, tolerancia e intervalos por día. Solo lectura.')]
class HorarioDeEmpleado extends ReadOnlyAttendanceTool
{
    protected string $name = 'horario_de_empleado';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'empleado' => ['required', 'string', 'max:64'],
        ]);

        return $this->respond($this->queries()->horarioDeEmpleado($validated['empleado']));
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
        ];
    }
}
