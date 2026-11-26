<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Plantilla para el resumen diario de asistencia: cuántos llegaron, cuántos van tarde, quién no ha marcado y quién no ha registrado salida.')]
class ResumenDelDia extends Prompt
{
    protected string $name = 'resumen_del_dia';

    protected string $title = 'Resumen del día';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        $fecha = $validated['fecha'] ?? now()->toDateString();
        $sede = $validated['sede'] ?? null;
        $departamento = $validated['departamento'] ?? null;

        $filtros = [];

        if ($sede !== null) {
            $filtros[] = 'sede: '.$sede;
        }

        if ($departamento !== null) {
            $filtros[] = 'departamento: '.$departamento;
        }

        $filtrosTexto = $filtros === [] ? 'sin filtros adicionales (se usa la sede del token)' : implode(' · ', $filtros);

        $texto = <<<MARKDOWN
Resume la asistencia del día {$fecha} con los datos del servidor MCP ({$filtrosTexto}).

Llamadas, en este orden:
1. `catalogo_sedes_departamentos` solo si necesitas confirmar el nombre exacto de una sede o departamento antes de filtrar.
2. `entradas_del_dia` con `fecha` = {$fecha}: primera marcación por empleado, diferencia con el horario y `empleados_sin_marcacion`.
3. `tardanzas_del_dia` con `fecha` = {$fecha}: solo quienes llegaron tarde, con minutos y si están justificadas.
4. `salidas_del_dia` con `fecha` = {$fecha}: última marcación del día y `empleados_sin_salida`.

Cómo responder:
- Primero los números: empleados que marcaron, cuántos llegaron tarde, minutos acumulados de tardanza, cuántos no han marcado y cuántos siguen sin salida.
- Después las listas cortas: los casos con más minutos de tardanza y los que siguen dentro (última marcación es una entrada).
- Si `truncated` es true, dilo y ofrece consultar por departamento o con un `limite` menor.
- No inventes nombres, horas ni minutos: usa únicamente lo que devuelvan las herramientas y aclara la hora de corte de los datos (zona horaria de la aplicación).
- Si el día es hoy, aclara que "sin salida" puede ser normal porque la jornada sigue en curso.

Cierra con dos o tres casos que ameriten revisión y una advertencia breve de que un volumen raro (todo el mundo marcando a la misma hora) apunta a falla de reloj o sincronización.
MARKDOWN;

        return Response::text($texto);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('fecha', 'Fecha en formato YYYY-MM-DD. Por defecto, hoy.'),
            new Argument('sede', 'Código o nombre de sede. Si se omite, se usa la sede del token.'),
            new Argument('departamento', 'Nombre exacto del departamento.'),
        ];
    }
}
