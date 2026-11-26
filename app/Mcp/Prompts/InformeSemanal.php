<?php

namespace App\Mcp\Prompts;

use Carbon\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Plantilla para entregar el informe semanal de asistencia: marcaciones sin salida y tardanzas, con el rango, las llamadas al servidor y las reglas de clasificación ya resueltas.')]
class InformeSemanal extends Prompt
{
    protected string $name = 'informe_semanal';

    protected string $title = 'Informe semanal de asistencia';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'sede' => ['nullable', 'string', 'max:64'],
            'departamento' => ['nullable', 'string', 'max:120'],
        ]);

        [$desde, $hasta] = $this->resolveRange($validated['desde'] ?? null, $validated['hasta'] ?? null);

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

        $argumentos = json_encode(array_filter([
            'sede' => $sede,
            'departamento' => $departamento,
        ], fn ($value) => $value !== null), JSON_UNESCAPED_UNICODE);

        $texto = <<<MARKDOWN
Genera el informe semanal de asistencia de IntalnetAcces con los datos del servidor MCP.

Rango: {$desde} a {$hasta} ({$filtrosTexto}).
No inventes datos: si una respuesta llega vacía, dilo y revisa el día concreto.

1. Marcaciones incompletas (informe "Sin salida")
   - Llama a `marcaciones_incompletas` UNA VEZ POR DÍA, con `desde` y `hasta` iguales a cada fecha del rango. Nunca pidas el rango completo: se corta en 200 filas.
   - Argumentos base: {$argumentos}
   - Registra el `total` y el `truncated` de cada día e informa los días con 0 registros.
   - Clasifica por `problema`:
     - `sin_salida` y `marcacion_unica` → entran en la lista de "sin salida".
     - `sin_entrada` y `salida_no_valida` → no entran en la lista; cuéntalos aparte en las notas.

2. Tardanzas
   - Llama a `tardanzas_por_rango` una sola vez con el rango completo: {$desde} a {$hasta}.
   - Usa `resumen_por_empleado` y verifica que cuadre con las filas del detalle.

3. Departamento
   - Ambas respuestas traen `departamento` vacío en muchas filas. Complétalo con `exportar_reporte` tipo `horas_trabajadas` del mismo rango (CSV con delimitador ";", columna Departamento) y cruza por nombre normalizado (sin acentos, minúsculas, espacios colapsados).
   - Si después del cruce sigue vacío, escribe "Sin asignar".

4. Entrega
   - Dos archivos, un informe por archivo: `sin_salida_{$desde}_a_{$hasta}.xlsx` y `tardanzas_{$desde}_a_{$hasta}.xlsx`. Si el cliente no puede generar archivos, entrega el CSV de `exportar_reporte` (tipos `marcaciones_incompletas` y `tardanzas`) y dilo.
   - Hojas del informe sin salida: Sin salida, Resumen por empleado, Notas.
   - Hojas del informe de tardanzas: Tardanzas, Resumen por empleado, Notas.
   - En Notas deja: herramienta usada, rango exacto, total y truncated de cada llamada, reglas de clasificación, origen del departamento, días sin datos y casos excluidos.

5. Lectura crítica (obligatoria en la respuesta)
   - Volumen alto concentrado en un solo día o en una sola hora suele ser falla de reloj o de sincronización, no descargos.
   - Tardanzas repetidas del mismo empleado a la misma hora (por ejemplo turno nocturno entrando ~12:00 con ~360 minutos) son horario mal configurado, no impuntualidad.
   - Varias personas con la misma hora y los mismos minutos exactos apuntan a la tolerancia o al corte de turno del sistema.
   - Recomienda validar con jefaturas antes de usar los archivos como soporte disciplinario.

Límites del servidor: 200 filas por respuesta en las herramientas de consulta, 1000 en `exportar_reporte`, 60 peticiones por minuto por token.
MARKDOWN;

        return Response::text($texto);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('desde', 'Fecha inicial en formato YYYY-MM-DD. Por defecto, el lunes de la semana pasada.'),
            new Argument('hasta', 'Fecha final en formato YYYY-MM-DD. Por defecto, el domingo de la semana pasada.'),
            new Argument('sede', 'Código o nombre de sede. Si se omite, se usa la sede del token.'),
            new Argument('departamento', 'Nombre exacto del departamento.'),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveRange(?string $desde, ?string $hasta): array
    {
        if ($desde === null && $hasta === null) {
            $inicio = now()->startOfWeek(Carbon::MONDAY)->subWeek();
            $fin = $inicio->copy()->addDays(6);

            return [$inicio->toDateString(), $fin->toDateString()];
        }

        if ($desde === null) {
            return [Carbon::parse($hasta)->subDays(6)->toDateString(), $hasta];
        }

        if ($hasta === null) {
            return [$desde, Carbon::parse($desde)->addDays(6)->toDateString()];
        }

        return [$desde, $hasta];
    }
}
