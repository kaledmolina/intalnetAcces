<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Catálogo de sedes y departamentos disponibles para el token, con el horario y los empleados activos de cada departamento. Úsalo para obtener códigos y nombres exactos antes de filtrar. Solo lectura.')]
class CatalogoSedesDepartamentos extends ReadOnlyAttendanceTool
{
    protected string $name = 'catalogo_sedes_departamentos';

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond($this->queries()->catalogoSedesDepartamentos());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
