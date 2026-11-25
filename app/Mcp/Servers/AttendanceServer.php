<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AusenciasDelPeriodo;
use App\Mcp\Tools\BuscarEmpleado;
use App\Mcp\Tools\CatalogoSedesDepartamentos;
use App\Mcp\Tools\DetalleDeEmpleado;
use App\Mcp\Tools\EntradasDelDia;
use App\Mcp\Tools\ExportarReporte;
use App\Mcp\Tools\HorarioDeEmpleado;
use App\Mcp\Tools\HorasTrabajadas;
use App\Mcp\Tools\IncidenciasDelPeriodo;
use App\Mcp\Tools\MarcacionesDeEmpleado;
use App\Mcp\Tools\MarcacionesIncompletas;
use App\Mcp\Tools\RankingTardanzas;
use App\Mcp\Tools\ResumenAsistenciaPorSede;
use App\Mcp\Tools\TardanzasDelDia;
use App\Mcp\Tools\TardanzasPorRango;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('IntalnetAcces Asistencia')]
#[Version('1.0.0')]
#[Instructions('Servidor de solo lectura para consultar tardanzas, marcaciones, horarios y asistencia de IntalnetAcces. El tenant y la sede se derivan exclusivamente del token autenticado; no acepte ni envíe identificadores de empresa. Use catalogo_sedes_departamentos para obtener nombres exactos antes de filtrar.')]
class AttendanceServer extends Server
{
    protected array $tools = [
        TardanzasDelDia::class,
        EntradasDelDia::class,
        TardanzasPorRango::class,
        ResumenAsistenciaPorSede::class,
        MarcacionesDeEmpleado::class,
        BuscarEmpleado::class,
        HorarioDeEmpleado::class,
        RankingTardanzas::class,
        AusenciasDelPeriodo::class,
        MarcacionesIncompletas::class,
        HorasTrabajadas::class,
        DetalleDeEmpleado::class,
        CatalogoSedesDepartamentos::class,
        IncidenciasDelPeriodo::class,
        ExportarReporte::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
