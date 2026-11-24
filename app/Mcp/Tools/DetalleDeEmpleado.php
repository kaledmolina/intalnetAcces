<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Ficha consolidada de un empleado: datos básicos, sede, horario vigente, última marcación, marcaciones de hoy, tardanzas del mes y notas recientes. Solo lectura.')]
class DetalleDeEmpleado extends ReadOnlyAttendanceTool
{
    protected string $name = 'detalle_de_empleado';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'empleado' => ['required', 'string', 'max:64'],
        ]);

        return $this->respond($this->queries()->detalleDeEmpleado($validated['empleado']));
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
