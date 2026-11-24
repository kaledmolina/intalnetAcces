<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReadOnlyAttendanceTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Tardanzas registradas en un día (por defecto hoy), con filtro opcional por sede. Solo lectura.')]
class TardanzasDelDia extends ReadOnlyAttendanceTool
{
    protected string $name = 'tardanzas_del_dia';

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sede' => ['nullable', 'string', 'max:64'],
        ]);

        return $this->respond(
            $this->queries()->tardanzasDelDia($validated['fecha'] ?? null, $validated['sede'] ?? null)
        );
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
                ->description('Código o nombre de sede, por ejemplo SEDE-001.')
                ->nullable(),
        ];
    }
}
