@extends('layouts.app')

@section('title', $employee->name . ' - Detalle Empleado')
@section('page-header', 'Perfil de Empleado')
@section('page-sub-header', 'Historial de marcaciones y horario asignado')

@section('content')
<div class="space-y-6">

    <!-- Profile Header Card (Minimalist B&W) -->
    <div class="bw-card p-6 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-2xl bg-black flex items-center justify-center font-heading font-extrabold text-2xl text-white shadow-md">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight leading-none">{{ $employee->name }}</h2>
                    <span class="font-mono text-[10px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-bold self-start sm:self-auto">
                        ID: #{{ $employee->employee_no }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1.5 font-bold flex items-center">
                    <span class="inline-block w-2 h-2 rounded-full bg-black mr-2"></span>
                    {{ $employee->department->name ?? 'Sin departamento' }} • {{ $employee->position ?? 'Sin cargo' }}
                </p>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Cédula / Doc: {{ $employee->document_id ?? 'No especificado' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <a href="{{ route('employees.edit', $employee) }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-extrabold rounded-xl transition-all flex items-center shadow-sm">
                <i data-lucide="edit" class="w-3.5 h-3.5 mr-2"></i> Editar Perfil
            </a>

            <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('¿Seguro de eliminar este empleado?')" class="w-full md:w-auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 bg-white hover:bg-red-50 text-slate-700 hover:text-red-600 border border-slate-200 hover:border-red-200 text-xs font-extrabold rounded-xl transition-all flex items-center justify-center shadow-sm">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Info Horario y Datos (Minimalist B&W) -->
        <div class="space-y-6">
            <div class="bw-card p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center border-b border-slate-100 pb-3">
                    <i data-lucide="calendar" class="w-4 h-4 mr-2 text-black"></i>
                    Horario Asignado
                </h3>

                @if($currentSchedule)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="font-extrabold text-slate-900 text-base leading-tight">{{ $currentSchedule->name }}</p>
                            @if($currentSchedule->is_default)
                                <span class="bg-black text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-full border border-black shadow-sm tracking-wider">
                                    Defecto
                                </span>
                            @endif
                        </div>
                        
                        <div class="space-y-1.5 pt-2">
                            @foreach($currentSchedule->days->sortBy('day_of_week') as $day)
                                @php
                                    $diasSemana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
                                @endphp
                                <div class="flex justify-between font-bold text-xs py-1.5 border-b border-slate-100 last:border-0">
                                    <span class="text-slate-500">{{ $diasSemana[$day->day_of_week] }}</span>
                                    @if($day->is_working_day)
                                        <span class="font-mono text-slate-900 font-extrabold">{{ substr($day->entry_time, 0, 5) }} - {{ substr($day->exit_time, 0, 5) }}</span>
                                    @else
                                        <span class="text-slate-400 font-medium italic">Día Libre</span>
                                    @endif
                                </div>
                            @endforeach
                            
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-bold">Minutos de Tolerancia:</span>
                                <span class="font-extrabold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">{{ $currentSchedule->tolerance_minutes }} min</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-6 text-slate-400">
                        <i data-lucide="slash" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold">No tiene un horario asignado.</p>
                        <p class="text-[10px] text-slate-400 mt-1">El sistema aplicará el horario marcado como predeterminado.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pestañas de Asistencia (Horas Trabajadas y Registro de Marcaciones) -->
        <div class="md:col-span-2 bw-card rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <!-- Encabezado de Pestañas -->
                <div class="border-b border-slate-100 flex px-6 bg-slate-50">
                    <div class="flex space-x-6 text-xs font-black uppercase tracking-wider text-slate-500">
                        <button onclick="switchTab('hours')" id="tab-btn-hours" class="py-4 border-b-2 border-black text-slate-900 focus:outline-none flex items-center space-x-1.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span>Horas Trabajadas</span>
                        </button>
                        <button onclick="switchTab('punches')" id="tab-btn-punches" class="py-4 border-b-2 border-transparent hover:text-slate-800 text-slate-500 focus:outline-none flex items-center space-x-1.5">
                            <i data-lucide="history" class="w-3.5 h-3.5"></i>
                            <span>Historial de Marcaciones</span>
                        </button>
                    </div>
                </div>

                <!-- Filtros Globales de Fecha para el Historial -->
                <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filtro de Historial</span>
                    <form method="GET" action="{{ route('employees.show', $employee) }}" class="flex items-center space-x-2">
                        <input type="date" name="date" value="{{ request('date') }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg focus:ring-black focus:border-black block px-2.5 py-1.5 font-medium">
                        <button type="submit" class="bg-black hover:bg-slate-800 text-white font-bold rounded-lg text-xs px-3 py-1.5 transition-colors">
                            Filtrar
                        </button>
                        @if(request()->filled('date'))
                            <a href="{{ route('employees.show', $employee) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg text-xs px-3 py-1.5 transition-colors">
                                Limpiar
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Contenido Pestaña 1: Horas Trabajadas -->
                <div id="tab-content-hours" class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-800">
                            <thead class="bg-slate-50/50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3.5">Fecha</th>
                                    <th class="px-6 py-3.5">Primer Marcaje (Entrada)</th>
                                    <th class="px-6 py-3.5">Último Marcaje (Salida)</th>
                                    <th class="px-6 py-3.5 text-right">Horas Laboradas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($dailyHours as $row)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-slate-900 text-xs">
                                            {{ \Carbon\Carbon::parse($row->event_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                                            {{ $row->check_in_time }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold">
                                            @if($row->check_out_time === 'Falta Salida')
                                                <span class="text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-lg font-black text-[9px] uppercase tracking-wide">Falta Salida</span>
                                            @else
                                                <span class="text-slate-600">{{ $row->check_out_time }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs font-black text-slate-900">
                                            <div class="flex flex-col items-end space-y-1">
                                                @if($row->hours_worked_text !== '—')
                                                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">{{ $row->hours_worked_text }}</span>
                                                @else
                                                    <span class="text-slate-400 font-medium">—</span>
                                                @endif
                                                
                                                @if($row->extra_hours_text)
                                                    <span class="text-[10px] text-green-700 font-bold bg-green-50 px-2 rounded-md border border-green-200">
                                                        + Extras: {{ $row->extra_hours_text }}
                                                    </span>
                                                @endif

                                                @if(count($row->notes) > 0)
                                                    @foreach($row->notes as $note)
                                                        <span class="text-[9px] text-amber-600 font-bold italic block mt-1">
                                                            {{ $note }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                            <i data-lucide="clock" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                            <p class="text-xs font-bold">Sin registros de horas.</p>
                                            <p class="text-[10px] mt-1 text-slate-400">Se necesitan al menos 2 marcaciones el mismo día para calcular horas laboradas.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($dailyHours->hasPages())
                        <div class="p-4 border-t border-slate-100 bg-slate-50">
                            {{ $dailyHours->appends(['punches_page' => request('punches_page')])->links('partials.pagination') }}
                        </div>
                    @endif
                </div>

                <!-- Contenido Pestaña 2: Registro de Marcaciones (Original) -->
                <div id="tab-content-punches" class="p-0 hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-800">
                            <thead class="bg-slate-50/50 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3.5">Fecha y Hora</th>
                                    <th class="px-6 py-3.5">Huellero / Ubicación</th>
                                    <th class="px-6 py-3.5">Estado de Entrada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($attendanceRecords as $record)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-900 text-xs">{{ $record->event_time->format('d/m/Y') }}</span>
                                                <span class="font-mono text-[10px] text-slate-400 font-bold mt-0.5">{{ $record->event_time->format('h:i:s A') }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-600">
                                            <div class="flex items-center space-x-1.5">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                                <span>{{ $record->device ? $record->device->name : 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($record->is_late)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200 tracking-wide">
                                                    Tardanza (+{{ $record->late_minutes }} min)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 tracking-wide">
                                                    A tiempo
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-slate-400">
                                            <i data-lucide="info" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                            <p class="text-xs font-bold">Sin marcaciones registradas para este empleado.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($attendanceRecords->hasPages())
                        <div class="p-4 border-t border-slate-100 bg-slate-50">
                            {{ $attendanceRecords->appends(['hours_page' => request('hours_page')])->links('partials.pagination') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>



@push('scripts')
<script>
    function switchTab(tabName) {
        const hoursTab = document.getElementById('tab-content-hours');
        const punchesTab = document.getElementById('tab-content-punches');
        const hoursBtn = document.getElementById('tab-btn-hours');
        const punchesBtn = document.getElementById('tab-btn-punches');

        if (tabName === 'hours') {
            hoursTab.classList.remove('hidden');
            punchesTab.classList.add('hidden');
            hoursBtn.className = "py-4 border-b-2 border-black text-slate-900 focus:outline-none flex items-center space-x-1.5";
            punchesBtn.className = "py-4 border-b-2 border-transparent hover:text-slate-800 text-slate-500 focus:outline-none flex items-center space-x-1.5";
        } else {
            hoursTab.classList.add('hidden');
            punchesTab.classList.remove('hidden');
            hoursBtn.className = "py-4 border-b-2 border-transparent hover:text-slate-800 text-slate-500 focus:outline-none flex items-center space-x-1.5";
            punchesBtn.className = "py-4 border-b-2 border-black text-slate-900 focus:outline-none flex items-center space-x-1.5";
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('punches_page')) {
            switchTab('punches');
        } else {
            switchTab('hours');
        }
    });


</script>
@endpush
@endsection
