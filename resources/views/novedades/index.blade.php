@extends('layouts.app')

@section('title', 'Novedades de Asistencia - IntalnetAcces')
@section('page-header', 'Novedades de Asistencia')
@section('page-sub-header', 'Consolidado mensual de puntualidad, llegadas tarde y salidas anticipadas')

@section('content')
<div class="space-y-6">

    <!-- 1. BARRA SUPERIOR: SELECTOR DE MES Y ACCIONES -->
    <div class="bw-card p-5 rounded-2xl flex flex-col lg:flex-row items-center justify-between gap-4 shadow-sm bg-white border border-slate-200">
        <div class="flex items-center space-x-3.5 w-full lg:w-auto">
            <div class="p-3 bg-black text-white rounded-2xl shadow-sm flex items-center justify-center">
                <i data-lucide="calendar-clock" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="font-heading font-black text-lg text-slate-900 tracking-tight">
                        Consolidado de {{ ucfirst($carbonMonth->isoFormat('MMMM YYYY')) }}
                    </h2>
                    @if($selectedMonth === now()->format('Y-m'))
                        <span class="bg-black text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full shadow-xs">
                            Mes en Curso
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    Monitoreo y análisis de cumplimiento de horarios corporativos
                </p>
            </div>
        </div>

        <!-- Controles de Selector de Mes y Exportación -->
        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-end">
            <!-- Navegación Mes Anterior -->
            <a href="{{ route('novedades.index', array_merge(request()->query(), ['month' => $prevMonth])) }}" 
               class="btn-hover-grow p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition-colors"
               title="Mes Anterior ({{ $prevMonth }})">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>

            <!-- Selector Dropdown de Meses -->
            <form method="GET" action="{{ route('novedades.index') }}" id="monthForm" class="inline-flex items-center">
                @if(request('department_id'))
                    <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                @endif
                <select name="month" onchange="this.form.submit()" 
                        class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-extrabold rounded-xl focus:ring-black focus:border-black block py-2.5 px-3 pr-8 shadow-xs cursor-pointer">
                    @foreach($availableMonths as $m)
                        <option value="{{ $m['value'] }}" {{ $selectedMonth === $m['value'] ? 'selected' : '' }}>
                            {{ $m['label'] }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Navegación Mes Siguiente -->
            <a href="{{ route('novedades.index', array_merge(request()->query(), ['month' => $nextMonth])) }}" 
               class="btn-hover-grow p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition-colors"
               title="Mes Siguiente ({{ $nextMonth }})">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>

            @if($selectedMonth !== now()->format('Y-m'))
                <!-- Botón Volver a Mes Actual -->
                <a href="{{ route('novedades.index', ['month' => now()->format('Y-m')]) }}" 
                   class="btn-hover-grow text-xs font-extrabold px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl border border-slate-300 shadow-xs flex items-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Hoy</span>
                </a>
            @endif

            <!-- Botón Exportar CSV -->
            <a href="{{ route('novedades.export', request()->query()) }}" 
               class="btn-hover-grow text-xs font-black px-4 py-2.5 bg-black hover:bg-slate-800 text-white rounded-xl shadow-md flex items-center space-x-1.5 whitespace-nowrap">
                <i data-lucide="download" class="w-4 h-4 text-white"></i>
                <span>Exportar CSV</span>
            </a>
        </div>
    </div>

    <!-- 2. CONSOLIDADO DE TODO EL MES (KPI CARDS EN ALTO CONTRASTE) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: TOTAL NOVEDADES -->
        <div class="bw-card p-5 rounded-2xl space-y-3 bg-white border border-slate-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Novedades del Mes</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalNoveltiesCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Incidencias registradas en {{ ucfirst($carbonMonth->isoFormat('MMMM')) }}</span>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-600">
                <span>{{ $totalLateCount }} tardanzas</span>
                <span>•</span>
                <span>{{ $totalEarlyCount }} salidas antes</span>
            </div>
        </div>

        <!-- KPI 2: LLEGADAS TARDE -->
        <div class="bw-card p-5 rounded-2xl space-y-3 bg-white border border-slate-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Llegadas Tarde</span>
                <div class="p-2.5 bg-black text-white rounded-xl shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalLateCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Ingresos tras tolerancia</span>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-700">
                <span>Total: {{ \App\Http\Controllers\NoveltyController::formatMinutesHuman($totalLateMinutes) }}</span>
                <span class="text-slate-400">Prom: {{ $avgLateMinutes }}m</span>
            </div>
        </div>

        <!-- KPI 3: SALIDAS TEMPRANAS -->
        <div class="bw-card p-5 rounded-2xl space-y-3 bg-white border border-slate-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Salidas Tempranas</span>
                <div class="p-2.5 bg-slate-200 text-slate-800 rounded-xl border border-slate-300">
                    <i data-lucide="door-open" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalEarlyCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Salidas antes de horario</span>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-700">
                <span>Total: {{ \App\Http\Controllers\NoveltyController::formatMinutesHuman($totalEarlyExitMinutes) }}</span>
                <span class="text-slate-400">Prom: {{ $avgEarlyMinutes }}m</span>
            </div>
        </div>

        <!-- KPI 4: COLABORADORES CON NOVEDAD -->
        <div class="bw-card p-5 rounded-2xl space-y-3 bg-white border border-slate-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Personal con Novedad</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $uniqueEmployeesWithNovelties }}
                    <span class="text-base text-slate-400 font-normal">/ {{ $totalEmployeesCount }}</span>
                </span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Colaboradores con al menos 1 novedad</span>
            </div>
            @php 
                $noveltyPercent = $totalEmployeesCount > 0 ? round(($uniqueEmployeesWithNovelties / $totalEmployeesCount) * 100) : 0; 
            @endphp
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-black h-full rounded-full transition-all duration-500" style="width: {{ $noveltyPercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- 3. SECCIÓN GRÁFICA TIPO POR DÍA (GRÁFICO INTERACTIVO CHART.JS) -->
    <div class="bw-card p-6 rounded-2xl shadow-sm space-y-5 bg-white border border-slate-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h3 class="font-heading font-black text-base text-slate-900 flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-black mr-2"></span>
                    Distribución Gráfica Diaria de Novedades
                </h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    Haz clic en cualquier barra o día para filtrar instantáneamente el personal que llegó tarde o se fue temprano ese día
                </p>
            </div>

            <!-- Selector de Serie para el Gráfico -->
            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200 shrink-0">
                <button type="button" onclick="setChartDataset('all')" id="btn-chart-all"
                        class="px-3 py-1.5 rounded-lg text-xs font-black transition-all bg-black text-white shadow-xs">
                    Todas
                </button>
                <button type="button" onclick="setChartDataset('late')" id="btn-chart-late"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900">
                    Llegadas Tarde
                </button>
                <button type="button" onclick="setChartDataset('early')" id="btn-chart-early"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900">
                    Salidas Tempranas
                </button>
            </div>
        </div>

        <!-- Canvas de Chart.js -->
        <div class="w-full min-w-0 relative h-72">
            <canvas id="monthlyNoveltiesChart"></canvas>
        </div>

        <!-- Leyenda Informativa y Explicación -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-3">
            <div class="flex items-center space-x-5">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-md bg-black inline-block"></span>
                    <span class="font-extrabold text-slate-800">Llegadas Tarde (Entrada)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-md bg-slate-400 inline-block"></span>
                    <span class="font-extrabold text-slate-800">Salidas Tempranas (Retiro antes de hora)</span>
                </div>
            </div>
            @if($worstDay && $worstDay['total_count'] > 0)
                <div class="text-[11px] font-bold text-slate-600 bg-slate-50 px-3 py-1 rounded-lg border border-slate-200">
                    ⚡ Pico del mes: <strong class="text-slate-900">Día {{ $worstDay['day'] }} ({{ $worstDay['date'] }})</strong> con {{ $worstDay['total_count'] }} incidencias
                </div>
            @endif
        </div>
    </div>

    <!-- 4. SELECTOR VISUAL RÁPIDO DÍA A DÍA (PÍLDORAS DE DÍAS DEL MES) -->
    <div class="bw-card p-5 rounded-2xl shadow-sm bg-white border border-slate-200 space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i data-lucide="calendar" class="w-4 h-4 text-slate-700"></i>
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">
                    Calendario de Días del Mes
                </h4>
            </div>
            <button type="button" onclick="selectDayFilter(null)" id="btnResetDayFilter" 
                    class="text-xs font-black text-black hover:underline cursor-pointer {{ empty($filterDay) ? 'hidden' : '' }}">
                ✕ Quitar filtro de día (Ver mes completo)
            </button>
        </div>

        <!-- Barra Scrollable Horizontal de Días -->
        <div class="overflow-x-auto pb-2 -mx-1 px-1">
            <div class="flex items-center space-x-2 min-w-max">
                <!-- Botón Todo el Mes -->
                <button type="button" onclick="selectDayFilter(null)" 
                        id="day-pill-all"
                        class="day-filter-pill px-3.5 py-2 rounded-xl text-xs font-black border transition-all text-center flex flex-col items-center justify-center min-w-[70px] {{ empty($filterDay) ? 'bg-black text-white border-black shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                    <span class="text-[10px] uppercase font-bold text-slate-400 leading-tight">TODO</span>
                    <span class="text-sm font-black mt-0.5">Mes</span>
                    <span class="text-[10px] font-bold mt-0.5 opacity-80">{{ $totalNoveltiesCount }}</span>
                </button>

                @foreach($dailyStats as $dayNum => $st)
                    <button type="button" onclick="selectDayFilter({{ $dayNum }})" 
                            id="day-pill-{{ $dayNum }}"
                            class="day-filter-pill px-2.5 py-2 rounded-xl text-xs font-bold border transition-all text-center flex flex-col items-center justify-center min-w-[58px] relative {{ (string)$filterDay === (string)$dayNum ? 'bg-black text-white border-black shadow-sm' : ($st['is_weekend'] ? 'bg-slate-50/50 text-slate-400 border-slate-100 hover:bg-slate-100' : 'bg-white text-slate-700 border-slate-200 hover:border-black') }}">
                        <span class="text-[9px] uppercase font-extrabold {{ (string)$filterDay === (string)$dayNum ? 'text-slate-300' : 'text-slate-500' }}">
                            {{ $st['day_short'] }}
                        </span>
                        <span class="text-sm font-black mt-0.5 {{ (string)$filterDay === (string)$dayNum ? 'text-white' : 'text-slate-900' }}">
                            {{ $dayNum }}
                        </span>
                        
                        <!-- Badges de conteo -->
                        <div class="flex items-center gap-1 mt-1">
                            @if($st['late_count'] > 0)
                                <span class="w-4 h-4 rounded-full bg-black text-white text-[9px] font-black flex items-center justify-center" title="{{ $st['late_count'] }} llegadas tarde">
                                    {{ $st['late_count'] }}
                                </span>
                            @endif
                            @if($st['early_count'] > 0)
                                <span class="w-4 h-4 rounded-full bg-slate-300 text-slate-900 text-[9px] font-black flex items-center justify-center" title="{{ $st['early_count'] }} salidas temprano">
                                    {{ $st['early_count'] }}
                                </span>
                            @endif
                            @if($st['total_count'] === 0)
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 5. RANKINGS MENSUALES: TOP EMPLEADOS CON MÁS TARDANZAS Y SALIDAS TEMPRANAS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- TOP 5 LLEGADAS TARDE -->
        <div class="bw-card p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2 bg-black text-white rounded-xl shadow-xs">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="font-heading font-black text-sm text-slate-900">Top Llegadas Tarde</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Colaboradores con mayor número de tardanzas</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-slate-400">Total: {{ $totalLateCount }}</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($topLateEmployees as $empId => $data)
                    <div class="py-2.5 flex items-center justify-between gap-3 hover:bg-slate-50 px-2 rounded-xl transition-colors">
                        <div class="flex items-center space-x-3 min-w-0">
                            <span class="font-black text-xs text-slate-400 w-4">#{{ $loop->iteration }}</span>
                            <div class="w-8 h-8 rounded-lg bg-black text-white text-xs font-black flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($data['employee']->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-extrabold text-slate-900 truncate" title="{{ strtoupper($data['employee']->name) }}">
                                    {{ strtoupper($data['employee']->name) }}
                                </p>
                                <p class="text-[10px] text-slate-500 font-medium truncate">
                                    {{ $data['employee']->department?->name ?? 'Sin Departamento' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="bg-black text-white text-xs font-black px-2.5 py-0.5 rounded-full inline-block shadow-xs">
                                {{ $data['count'] }} {{ $data['count'] === 1 ? 'tardanza' : 'tardanzas' }}
                            </span>
                            <span class="text-[10px] text-slate-500 font-bold block mt-0.5">
                                +{{ \App\Http\Controllers\NoveltyController::formatMinutesHuman($data['total_minutes']) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400 font-medium text-xs">
                        <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                        No se registraron tardanzas en este mes.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- TOP 5 SALIDAS TEMPRANAS -->
        <div class="bw-card p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2 bg-slate-200 text-slate-900 rounded-xl border border-slate-300">
                        <i data-lucide="door-open" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="font-heading font-black text-sm text-slate-900">Top Salidas Tempranas</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Colaboradores que salieron antes del horario programado</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-slate-400">Total: {{ $totalEarlyCount }}</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($topEarlyEmployees as $empId => $data)
                    <div class="py-2.5 flex items-center justify-between gap-3 hover:bg-slate-50 px-2 rounded-xl transition-colors">
                        <div class="flex items-center space-x-3 min-w-0">
                            <span class="font-black text-xs text-slate-400 w-4">#{{ $loop->iteration }}</span>
                            <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-900 border border-slate-300 text-xs font-black flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($data['employee']->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-extrabold text-slate-900 truncate" title="{{ strtoupper($data['employee']->name) }}">
                                    {{ strtoupper($data['employee']->name) }}
                                </p>
                                <p class="text-[10px] text-slate-500 font-medium truncate">
                                    {{ $data['employee']->department?->name ?? 'Sin Departamento' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="bg-slate-200 text-slate-900 border border-slate-300 text-xs font-black px-2.5 py-0.5 rounded-full inline-block">
                                {{ $data['count'] }} {{ $data['count'] === 1 ? 'salida antes' : 'salidas antes' }}
                            </span>
                            <span class="text-[10px] text-slate-500 font-bold block mt-0.5">
                                {{ \App\Http\Controllers\NoveltyController::formatMinutesHuman($data['total_minutes']) }} anticipado
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400 font-medium text-xs">
                        <i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                        No se registraron retiros anticipados en este mes.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 6. PANEL DE FILTROS Y TABLA DETALLADA DE PERSONAS POR DÍA -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
        <!-- Encabezado de la Tabla con Filtros -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="font-heading font-black text-base text-slate-900 flex items-center">
                    <i data-lucide="list-filter" class="w-4 h-4 mr-2 text-black"></i>
                    Detalle de Personal y Novedades del Mes
                </h3>
                <p id="table-subtitle" class="text-xs text-slate-500 font-medium mt-0.5">
                    Mostrando <strong id="visible-count">{{ count($filteredNovelties) }}</strong> de <strong>{{ $totalNoveltiesCount }}</strong> incidencias en total
                </p>
            </div>

            <!-- Controles de Filtros Dinámicos -->
            <form method="GET" action="{{ route('novedades.index') }}" id="filtersForm" class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="day" id="filterDayInput" value="{{ $filterDay }}">

                <!-- Selector de Tipo de Novedad -->
                <select name="type" onchange="this.form.submit()" 
                        class="bg-white border border-slate-300 text-slate-900 text-xs font-bold rounded-xl focus:ring-black focus:border-black py-2 px-3 shadow-xs">
                    <option value="all" {{ $filterType === 'all' ? 'selected' : '' }}>Todas las Novedades</option>
                    <option value="late" {{ $filterType === 'late' ? 'selected' : '' }}>Solo Llegadas Tarde</option>
                    <option value="early_exit" {{ $filterType === 'early_exit' ? 'selected' : '' }}>Solo Salidas Tempranas</option>
                </select>

                <!-- Selector de Departamento -->
                <select name="department_id" onchange="this.form.submit()" 
                        class="bg-white border border-slate-300 text-slate-900 text-xs font-bold rounded-xl focus:ring-black focus:border-black py-2 px-3 shadow-xs">
                    <option value="">Todos los Departamentos</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string)$filterDept === (string)$dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Buscador de Empleado -->
                <div class="relative w-full sm:w-48">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </div>
                    <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Buscar empleado..." 
                           class="bg-white border border-slate-300 text-slate-900 text-xs font-medium rounded-xl focus:ring-black focus:border-black block w-full pl-8 py-2 pr-2.5 shadow-xs">
                </div>

                @if(!empty($filterDay) || !empty($searchQuery) || $filterType !== 'all' || !empty($filterDept))
                    <a href="{{ route('novedades.index', ['month' => $selectedMonth]) }}" 
                       class="btn-hover-grow p-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl transition-colors"
                       title="Restablecer filtros">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </form>
        </div>

        <!-- Banner de Filtro de Día Activo -->
        @if(!empty($filterDay))
            <div class="bg-black text-white px-5 py-2.5 flex items-center justify-between text-xs font-bold">
                <span class="flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                    <span>Filtrando por el <strong>Día {{ $filterDay }}</strong> de {{ ucfirst($carbonMonth->isoFormat('MMMM YYYY')) }}</span>
                </span>
                <button type="button" onclick="selectDayFilter(null)" class="text-white hover:underline text-xs font-extrabold flex items-center gap-1">
                    <span>Ver Todo el Mes</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        @endif

        <!-- Tabla de Novedades -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Fecha</th>
                        <th scope="col" class="px-6 py-3.5">Empleado</th>
                        <th scope="col" class="px-6 py-3.5">Departamento / Cargo</th>
                        <th scope="col" class="px-6 py-3.5">Tipo de Novedad</th>
                        <th scope="col" class="px-6 py-3.5">Horario Esperado</th>
                        <th scope="col" class="px-6 py-3.5">Marcación Real</th>
                        <th scope="col" class="px-6 py-3.5">Desfase / Novedad</th>
                        <th scope="col" class="px-6 py-3.5">Terminal ISAPI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" id="noveltiesTableBody">
                    @forelse($filteredNovelties as $item)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-extrabold text-slate-900 block leading-tight">
                                    {{ $item['day_name'] }}
                                </span>
                                <span class="text-xs text-slate-500 font-semibold block mt-0.5">
                                    {{ $item['formatted_date'] }}
                                </span>
                            </td>

                            <!-- Empleado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl {{ $item['type'] === 'late' ? 'bg-black text-white' : 'bg-slate-800 text-white' }} font-black text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ strtoupper(substr($item['employee_name'], 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 leading-tight">
                                            {{ strtoupper($item['employee_name']) }}
                                        </p>
                                        <p class="text-xs text-slate-500 font-mono font-semibold mt-0.5">
                                            ID: #{{ $item['employee_no'] }} {{ !empty($item['document_id']) && $item['document_id'] !== 'N/A' ? '• CC: ' . $item['document_id'] : '' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Departamento y Cargo -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block text-xs">
                                    {{ $item['department_name'] }}
                                </span>
                                <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                                    {{ $item['position'] }}
                                </span>
                            </td>

                            <!-- Tipo de Novedad -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item['type'] === 'late')
                                    <span class="bg-black text-white text-xs font-black px-3 py-1 rounded-full border border-black inline-flex items-center shadow-xs">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 mr-1.5 text-white"></i>
                                        Llegada Tarde
                                    </span>
                                @else
                                    <span class="bg-slate-200 text-slate-900 text-xs font-black px-3 py-1 rounded-full border border-slate-400 inline-flex items-center shadow-xs">
                                        <i data-lucide="door-open" class="w-3.5 h-3.5 mr-1.5 text-slate-900"></i>
                                        Salida Temprana
                                    </span>
                                @endif
                            </td>

                            <!-- Horario Esperado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-extrabold text-slate-700 font-mono text-xs">
                                    {{ $item['scheduled_time'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block font-medium">
                                    {{ $item['schedule_name'] }}
                                </span>
                            </td>

                            <!-- Marcación Real -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-black text-slate-900 font-mono text-xs block">
                                    {{ $item['actual_time'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 font-mono block">
                                    {{ $item['actual_timestamp'] }}
                                </span>
                            </td>

                            <!-- Desfase / Novedad -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item['type'] === 'late')
                                    <span class="text-xs font-black text-red-600 bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg inline-flex items-center">
                                        +{{ $item['formatted_diff'] }}
                                    </span>
                                @else
                                    <span class="text-xs font-black text-slate-800 bg-slate-100 border border-slate-300 px-2.5 py-1 rounded-lg inline-flex items-center">
                                        {{ $item['formatted_diff'] }}
                                    </span>
                                @endif
                            </td>

                            <!-- Terminal ISAPI -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-800 text-xs font-bold px-2.5 py-1 rounded-lg border border-slate-300 inline-flex items-center">
                                    <i data-lucide="cpu" class="w-3 h-3 mr-1.5 text-slate-600"></i>
                                    {{ $item['device_name'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <i data-lucide="check-circle-2" class="w-10 h-10 mx-auto mb-2 text-slate-400"></i>
                                <p class="font-bold text-sm text-slate-700">No se encontraron novedades para los filtros seleccionados</p>
                                <p class="text-xs text-slate-400 mt-1">No hay registros de llegadas tarde ni retiros anticipados que coincidan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<script>
    // Datos provistos por el controlador
    const chartLabels = @json($chartLabels);
    const chartLateData = @json($chartLateData);
    const chartEarlyData = @json($chartEarlyData);
    const chartTooltipsData = @json($chartTooltipsData);
    const currentSelectedMonth = "{{ $selectedMonth }}";
    let activeDayFilter = "{{ $filterDay }}";

    let noveltiesChart = null;
    let currentSeriesMode = 'all'; // 'all', 'late', 'early'

    function initNoveltiesChart() {
        const ctx = document.getElementById('monthlyNoveltiesChart');
        if (!ctx) return;

        if (noveltiesChart) {
            noveltiesChart.destroy();
        }

        const datasets = [];

        if (currentSeriesMode === 'all' || currentSeriesMode === 'late') {
            datasets.push({
                label: 'Llegadas Tarde',
                data: chartLateData,
                backgroundColor: '#0f172a', // Solid Black / Dark Charcoal
                borderColor: '#000000',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 28,
            });
        }

        if (currentSeriesMode === 'all' || currentSeriesMode === 'early') {
            datasets.push({
                label: 'Salidas Tempranas',
                data: chartEarlyData,
                backgroundColor: '#94a3b8', // Slate Gray
                borderColor: '#64748b',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 28,
            });
        }

        noveltiesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                family: 'Inter',
                                size: 11,
                                weight: '600',
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#64748b',
                            font: {
                                family: 'Inter',
                                size: 11,
                                weight: '600',
                            },
                        },
                        grid: {
                            color: '#e2e8f0',
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#f1f5f9',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: {
                            family: 'Outfit',
                            size: 13,
                            weight: 'bold',
                        },
                        bodyFont: {
                            family: 'Inter',
                            size: 11,
                            weight: '500',
                        },
                        callbacks: {
                            title: function(context) {
                                const idx = context[0].dataIndex;
                                const item = chartTooltipsData[idx];
                                return `${item.day_name}, Día ${item.day} (${item.date})`;
                            },
                            afterBody: function(context) {
                                const idx = context[0].dataIndex;
                                const item = chartTooltipsData[idx];
                                const lines = [];

                                if (item.late_names.length > 0) {
                                    lines.push('');
                                    lines.push('⚠️ Llegaron Tarde:');
                                    item.late_names.slice(0, 4).forEach(name => lines.push('  • ' + name.toUpperCase()));
                                    if (item.late_names.length > 4) {
                                        lines.push(`  ... y ${item.late_names.length - 4} más`);
                                    }
                                }

                                if (item.early_names.length > 0) {
                                    lines.push('');
                                    lines.push('🚪 Salieron Temprano:');
                                    item.early_names.slice(0, 4).forEach(name => lines.push('  • ' + name.toUpperCase()));
                                    if (item.early_names.length > 4) {
                                        lines.push(`  ... y ${item.early_names.length - 4} más`);
                                    }
                                }

                                if (item.total > 0) {
                                    lines.push('');
                                    lines.push('👉 Haz clic para ver el detalle de este día');
                                }

                                return lines;
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements && elements.length > 0) {
                        const clickedIndex = elements[0].index;
                        const dayNumber = clickedIndex + 1;
                        selectDayFilter(dayNumber);
                    }
                }
            }
        });
    }

    function setChartDataset(mode) {
        currentSeriesMode = mode;

        document.getElementById('btn-chart-all').className = mode === 'all' 
            ? 'px-3 py-1.5 rounded-lg text-xs font-black transition-all bg-black text-white shadow-xs' 
            : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900';
            
        document.getElementById('btn-chart-late').className = mode === 'late' 
            ? 'px-3 py-1.5 rounded-lg text-xs font-black transition-all bg-black text-white shadow-xs' 
            : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900';

        document.getElementById('btn-chart-early').className = mode === 'early' 
            ? 'px-3 py-1.5 rounded-lg text-xs font-black transition-all bg-black text-white shadow-xs' 
            : 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900';

        initNoveltiesChart();
    }

    function selectDayFilter(dayNumber) {
        const input = document.getElementById('filterDayInput');
        if (input) {
            input.value = dayNumber || '';
        }
        document.getElementById('filtersForm').submit();
    }

    // Inicializar al cargar el DOM o al navegar con Livewire
    document.addEventListener('DOMContentLoaded', () => {
        initNoveltiesChart();
    });

    document.addEventListener('livewire:navigated', () => {
        initNoveltiesChart();
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
@endpush
@endsection
