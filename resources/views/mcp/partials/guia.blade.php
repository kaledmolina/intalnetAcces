{{-- Guía de uso del conector MCP. Se incluye en /mi-mcp y en /admin/mcp. --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-heading font-extrabold text-sm text-slate-900 flex items-center space-x-2">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Guía: todo lo que puedes pedirle a tu IA</span>
            </h3>
            <p class="text-xs text-slate-500 font-semibold mt-1 max-w-2xl">
                Pregunta en lenguaje natural y el asistente elige la herramienta y los filtros. No hace falta escribir nombres de herramientas: basta con decir qué necesitas y para qué fecha.
            </p>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <span class="bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">16 herramientas</span>
            <span class="bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">2 plantillas</span>
            <span class="bg-black text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase">Solo lectura</span>
        </div>
    </div>

    <div class="p-5 space-y-7">

        {{-- Ejemplos por caso de uso --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="sun" class="w-3.5 h-3.5"></i>
                    <span>El día de hoy</span>
                </p>
                <div class="space-y-1.5">
                    <p class="text-[11px] text-slate-700 font-semibold">«¿Quién llegó primero hoy?»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«¿Quiénes no han marcado salida todavía?»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«¿Cuántos llegaron tarde y cuántos minutos suman?»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Resumen del día en SEDE-INTALNET»</p>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    <span>Una persona</span>
                </p>
                <div class="space-y-1.5">
                    <p class="text-[11px] text-slate-700 font-semibold">«Busca a Ana Pérez»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«¿Cuál es el horario de Juan Gómez?»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Muéstrame las marcaciones de la cédula 12345678 del 1 al 15»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Dame la ficha completa de Pedro con sus tardanzas del mes»</p>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i>
                    <span>Periodos y tendencias</span>
                </p>
                <div class="space-y-1.5">
                    <p class="text-[11px] text-slate-700 font-semibold">«Ranking de tardanzas del mes por departamento»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«¿Quién faltó la semana pasada y está justificado?»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Marcaciones incompletas de octubre por sede»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Horas trabajadas contra horario en los últimos 30 días»</p>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                    <span>Informes y archivos</span>
                </p>
                <div class="space-y-1.5">
                    <p class="text-[11px] text-slate-700 font-semibold">«Genera el informe semanal de colaboradores sin salida»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Hazme el Excel de tardanzas de la semana pasada»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«CSV de novedades del 1 al 15 de noviembre»</p>
                    <p class="text-[11px] text-slate-700 font-semibold">«Reporte de marcaciones sin asignar de ayer»</p>
                </div>
            </div>
        </div>

        {{-- Herramientas --}}
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider">Herramientas que puede usar el asistente</p>
                <p class="text-[10px] text-slate-500 font-semibold">Las fechas van en <span class="font-mono">YYYY-MM-DD</span>, pero puedes decirlas en palabras.</p>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full min-w-[720px] text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] font-black tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Herramienta</th>
                            <th class="px-4 py-2.5">Qué te devuelve</th>
                            <th class="px-4 py-2.5">Datos que acepta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach([
                            ['catalogo_sedes_departamentos', 'Sedes, departamentos, horario y empleados activos de cada uno.', 'Sin parámetros'],
                            ['entradas_del_dia', 'Primera marcación de cada empleado, diferencia con su horario, quién llegó primero y quién no marcó.', 'fecha, sede, departamento, límite'],
                            ['salidas_del_dia', 'Última marcación del día, quién sigue sin salida y quién no marcó nada.', 'fecha, sede, departamento, límite'],
                            ['tardanzas_del_dia', 'Solo quienes llegaron tarde, con minutos y si tienen nota justificatoria.', 'fecha, sede'],
                            ['tardanzas_por_rango', 'Tardanzas del periodo con resumen por empleado.', 'desde, hasta, sede, departamento'],
                            ['ranking_tardanzas', 'Ranking por empleado o departamento, con minutos y comparación contra el periodo anterior.', 'desde, hasta, sede, departamento, agrupar, límite'],
                            ['resumen_asistencia_por_sede', 'Presentes, tardanzas y ausencias agrupadas por sede.', 'fecha o desde + hasta'],
                            ['incidencias_del_periodo', 'Quién llegó tarde y cuántas veces, y quién no registró salida, en 7 días o el mes.', 'periodo (7_dias o mes), sede, departamento'],
                            ['ausencias_del_periodo', 'Faltas por empleado y día, contando solo días laborables e indicando si están justificadas.', 'desde, hasta, sede, departamento'],
                            ['marcaciones_incompletas', 'Una sola marcación, sin entrada, sin salida o par demasiado corto: errores de reloj o sincronización.', 'desde, hasta, sede, departamento'],
                            ['horas_trabajadas', 'Jornada real frente a la programada: minutos, diferencia, días sin marcación e incompletos.', 'desde, hasta, sede, departamento'],
                            ['marcaciones_de_empleado', 'Hora, tipo y sede de cada marcación de una persona.', 'empleado, desde, hasta'],
                            ['buscar_empleado', 'Coincidencias por nombre, documento o número de empleado.', 'texto'],
                            ['horario_de_empleado', 'Jornada, tolerancia e intervalos por día del horario vigente.', 'empleado'],
                            ['detalle_de_empleado', 'Ficha consolidada: datos, sede, horario, última marcación, marcaciones de hoy, tardanzas del mes y notas.', 'empleado'],
                            ['exportar_reporte', 'CSV con delimitador «;» listo para Excel, hasta 1000 filas.', 'tipo, desde, hasta, sede, departamento'],
                        ] as [$herramienta, $devuelve, $acepta])
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="bg-slate-100 border border-slate-200 text-slate-800 text-[10px] font-mono font-bold px-2 py-0.5 rounded">{{ $herramienta }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 font-medium leading-relaxed">{{ $devuelve }}</td>
                                <td class="px-4 py-2.5 text-slate-500 font-mono text-[10px] leading-relaxed">{{ $acepta }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-[11px] text-slate-500 font-semibold mt-2 leading-relaxed">
                En <span class="font-mono">empleado</span> puedes usar el id interno, el número de documento o el número de empleado.
                En <span class="font-mono">exportar_reporte</span> los tipos son
                <span class="font-mono">tardanzas</span>, <span class="font-mono">ausencias</span>, <span class="font-mono">horas_trabajadas</span>,
                <span class="font-mono">marcaciones_incompletas</span>, <span class="font-mono">novedades</span> y <span class="font-mono">sin_asignar</span>.
            </p>
        </div>

        {{-- Plantillas y reglas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="layout-template" class="w-3.5 h-3.5"></i>
                    <span>Plantillas ya resueltas</span>
                </p>
                <div class="space-y-2">
                    <div class="bg-white border border-slate-200 rounded-lg p-3">
                        <p class="text-[11px] font-extrabold text-slate-900 font-mono">informe_semanal</p>
                        <p class="text-[11px] text-slate-600 font-medium mt-1 leading-relaxed">
                            Arma el informe semanal de «sin salida» y tardanzas, con reglas de clasificación y notas.
                            Si no das fechas, toma la semana pasada completa (lunes a domingo).
                        </p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-lg p-3">
                        <p class="text-[11px] font-extrabold text-slate-900 font-mono">resumen_del_dia</p>
                        <p class="text-[11px] text-slate-600 font-medium mt-1 leading-relaxed">
                            Resumen del día: cuántos marcaron, tardanzas, quién no ha marcado y quién sigue sin salida.
                            Si no das fecha, toma hoy.
                        </p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-lg p-3">
                        <p class="text-[11px] font-extrabold text-slate-900 font-mono">intalnet://docs/diccionario-de-campos</p>
                        <p class="text-[11px] text-slate-600 font-medium mt-1 leading-relaxed">
                            Diccionario de campos (<span class="font-mono">check_in</span>, <span class="font-mono">sin_salida</span>,
                            <span class="font-mono">truncated</span>…). Pídele al asistente que lo consulte si una respuesta no se entiende.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-black text-white rounded-xl p-4 space-y-3">
                <p class="text-[10px] uppercase font-black tracking-wider text-slate-400 flex items-center space-x-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>Límites y reglas</span>
                </p>
                <ul class="space-y-2 text-[11px] font-semibold text-slate-200 leading-relaxed">
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>Solo lectura: no puede modificar marcaciones, empleados, horarios ni notas.</span></li>
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>Alcance fijo: empresa y sede del token. Si pides otra sede, la respuesta llega vacía.</span></li>
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>Consultas de rango: máximo 92 días por llamada.</span></li>
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>200 filas por respuesta (1000 en <span class="font-mono text-emerald-300">exportar_reporte</span>); si se corta, la respuesta lo indica con <span class="font-mono text-emerald-300">truncated</span>.</span></li>
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>60 peticiones por minuto por token.</span></li>
                    <li class="flex items-start space-x-2"><span class="text-slate-500">•</span><span>Para filtrar por sede o departamento, pide primero el catálogo y usa el nombre exacto.</span></li>
                </ul>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
            <p class="text-[11px] text-amber-800 font-semibold leading-relaxed">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline mr-1"></i>
                Los archivos Excel (.xlsx) los arma el asistente a partir de los datos o del CSV del servidor. Si necesitas un .xlsx y el cliente no genera archivos, pide el CSV y ábrelo en Excel.
            </p>
        </div>
    </div>
</div>
