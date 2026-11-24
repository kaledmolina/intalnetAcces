<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BuscarEmpleado;
use App\Mcp\Tools\HorarioDeEmpleado;
use App\Mcp\Tools\MarcacionesDeEmpleado;
use App\Mcp\Tools\ResumenAsistenciaPorSede;
use App\Mcp\Tools\TardanzasDelDia;
use App\Mcp\Tools\TardanzasPorRango;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('IntalnetAcces Asistencia')]
#[Version('1.0.0')]
#[Instructions('Servidor de solo lectura para consultar tardanzas, marcaciones, horarios y asistencia de IntalnetAcces. El tenant y la sede se derivan exclusivamente del token autenticado; no acepte ni envíe identificadores de empresa.')]
class AttendanceServer extends Server
{
    protected array $tools = [
        TardanzasDelDia::class,
        TardanzasPorRango::class,
        ResumenAsistenciaPorSede::class,
        MarcacionesDeEmpleado::class,
        BuscarEmpleado::class,
        HorarioDeEmpleado::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
