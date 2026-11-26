<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('Diccionario de los campos y valores que devuelven las herramientas: check_in, check_out, sin_salida, truncated, límites y convenciones de fechas.')]
#[Uri('intalnet://docs/diccionario-de-campos')]
#[MimeType('text/markdown')]
class DiccionarioDeCampos extends Resource
{
    protected string $name = 'diccionario_de_campos';

    protected string $title = 'Diccionario de campos';

    public function handle(Request $request): Response
    {
        return Response::text(<<<'MARKDOWN'
# Diccionario de campos — IntalnetAcces Asistencia

Servidor de solo lectura. La empresa y la sede salen del token; ninguna herramienta acepta identificadores de empresa.

## Envoltura de las respuestas

| Campo | Significado |
| --- | --- |
| `herramienta` | Nombre de la herramienta que respondió. |
| `data` | Filas del resultado, ya limitadas. |
| `total` | Filas reales que cumplen el filtro, aunque se hayan recortado. |
| `truncated` | `true` cuando `total` supera el límite devuelto; hay más datos de los que ves. |
| `mensaje` | Texto con la causa cuando no hay datos o el resultado se recortó. |

Límites: 200 filas por herramienta de consulta, 1000 en `exportar_reporte`, 60 peticiones por minuto por token.

## Tipos de marcación (`attendance_type`)

| Valor | Significado |
| --- | --- |
| `check_in` | Entrada. |
| `check_out` | Salida. |
| `auto` | El equipo no distinguió entrada o salida; la aplicación lo deduce según el orden del día. |

## Tardanza

| Campo | Significado |
| --- | --- |
| `is_late` | La marcación se registró después del horario más la tolerancia. |
| `late_minutes` | Minutos de diferencia con la hora programada; se toma en valor absoluto al mostrarlo. |
| `justificada` | Existe una nota diaria (`employee_daily_notes`) para ese empleado y esa fecha. |

La tardanza se lee de `attendance_records`, no se recalcula en el servidor.

## Problemas de marcación (`problema`)

| Valor | Significado |
| --- | --- |
| `marcacion_unica` | Una sola marcación en el día: no se puede saber si entró o salió. |
| `sin_salida` | La última marcación del día es una entrada; la persona nunca registró la salida. |
| `sin_entrada` | La primera marcación del día es una salida. |
| `salida_no_valida` | Dos marcaciones separadas por menos de 30 minutos: parece un doble toque, no una jornada. |

`salidas_del_dia` usa la misma regla: hay salida solo si la **última** marcación del día es `check_out`. Una entrada posterior al checkout significa que la persona volvió a entrar.

## Novedades

`exportar_reporte` tipo `novedades` combina tres tipos en la columna `novedad`: `Llegada tarde`, `Salida temprana` (última marcación antes del horario de salida menos la tolerancia, con al menos 25 minutos de jornada) y las marcaciones incompletas (`Sin salida`, `Marcación única`, `Sin entrada`, `Salida no válida`).

## Empleados, sedes y departamentos

| Campo | Significado |
| --- | --- |
| `empleado` | Nombre tal como está en el sistema. |
| `documento` | Cédula o documento de identidad. |
| `sede` | Código de la sede del usuario dueño del empleado. |
| `departamento` | Puede venir vacío si el empleado no tiene departamento asignado o si la fila no lo trae; en ese caso se completa cruzando con `exportar_reporte` tipo `horas_trabajadas`. |
| `sin_asignar` | Marcaciones del huellero sin empleado asociado (`employee_id` nulo): el número de empleado o la tarjeta no cruzan con ningún registro. |

## Fechas y horas

- Los parámetros de fecha usan `YYYY-MM-DD`.
- Las horas se devuelven en ISO 8601 con la zona horaria de la aplicación (`APP_TIMEZONE`, por defecto `America/Bogota`).
- El rango máximo de las consultas por periodo es de 92 días.

## Exportes CSV

`exportar_reporte` devuelve `csv` (delimitador `;`), `filas`, `total` y `truncated`. Tipos disponibles: `tardanzas`, `ausencias`, `horas_trabajadas`, `marcaciones_incompletas`, `novedades` y `sin_asignar`. El tipo `sin_asignar` ignora `sede` y `departamento` porque esas marcaciones no tienen empleado.
MARKDOWN);
    }
}
