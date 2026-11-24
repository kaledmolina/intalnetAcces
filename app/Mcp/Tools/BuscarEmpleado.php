<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Busca empleados por nombre, documento o número de empleado dentro de la empresa del token.')]
class BuscarEmpleado extends ReadOnlyAttendanceTool
{
    protected string $name = 'buscar_empleado';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'texto' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return $this->respond($this->queries()->buscarEmpleado($validated['texto']));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()
                ->description('Texto libre para buscar por nombre, documento o número de empleado.')
                ->required(),
        ];
    }
}
