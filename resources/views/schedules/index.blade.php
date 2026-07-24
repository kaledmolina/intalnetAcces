@extends('layouts.app')

@section('title', 'Horarios - IntalnetAcces')
@section('page-header', 'Gestión de Horarios')
@section('page-sub-header', 'Configuración de turnos de trabajo y margen de tolerancia por días')

@section('content')
<div class="space-y-6">

    @php
        $diasSemana = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
        $diasCortos = [
            1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'
        ];
    @endphp

    <!-- Top Action Bar -->
    <div class="bw-card p-5 rounded-2xl flex items-center justify-between shadow-sm bg-white border border-slate-200">
        <div class="flex items-center space-x-3">
            <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-extrabold text-lg text-slate-900">Horarios Laborales</h3>
                <p class="text-xs text-slate-500 font-medium">Reglas de horas de entrada, salida y minutos de tolerancia</p>
            </div>
        </div>

        <button type="button" data-modal-target="scheduleModal" data-modal-toggle="scheduleModal" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 flex items-center space-x-2 shadow-md">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>+ Nuevo Horario</span>
        </button>
    </div>

    <!-- Schedules Grid (Black & White Style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($schedules as $schedule)
            <div class="bw-card p-6 rounded-2xl space-y-4 shadow-sm relative bg-white border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="font-heading font-extrabold text-base text-slate-900">{{ $schedule->name }}</h3>
                    @if($schedule->is_default)
                        <span class="bg-black text-white text-xs font-extrabold px-2.5 py-0.5 rounded-full border border-black shadow-sm">
                            Por Defecto
                        </span>
                    @endif
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-600 font-semibold">Tolerancia Global:</span>
                        <span class="font-extrabold text-slate-900 text-sm">{{ $schedule->tolerance_minutes }} min</span>
                    </div>

                    <div class="space-y-1.5">
                        <span class="text-slate-700 font-extrabold uppercase tracking-wider text-[10px]">Configuración por Días</span>
                        <div class="grid grid-cols-1 gap-1.5">
                            @foreach($schedule->days->sortBy('day_of_week') as $day)
                                @if($day->is_working_day)
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-100 p-2 rounded-lg">
                                        <span class="font-bold text-slate-700">{{ $diasSemana[$day->day_of_week] }}</span>
                                        <span class="font-mono text-slate-900 font-extrabold text-xs">
                                            {{ \Carbon\Carbon::parse($day->entry_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($day->exit_time)->format('H:i') }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-100 p-2 rounded-lg opacity-50">
                                        <span class="font-bold text-slate-500">{{ $diasSemana[$day->day_of_week] }}</span>
                                        <span class="text-slate-400 font-semibold text-xs italic">Libre</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @if($schedule->departments->count() > 0)
                        <div class="pt-2 mt-2 border-t border-slate-100">
                            <span class="text-slate-700 font-extrabold uppercase tracking-wider text-[10px] block mb-1.5">Departamentos Asignados</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($schedule->departments as $dept)
                                    <span class="bg-slate-100 text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded-md border border-slate-200">
                                        <i data-lucide="building-2" class="w-3 h-3 inline-block mr-0.5 -mt-0.5"></i>{{ $dept->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-200">
                    <button type="button" data-modal-target="assignDeptModal-{{ $schedule->id }}" data-modal-toggle="assignDeptModal-{{ $schedule->id }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 mr-1 text-slate-900"></i> Asignar Depto
                    </button>
                    <button type="button" data-modal-target="editScheduleModal-{{ $schedule->id }}" data-modal-toggle="editScheduleModal-{{ $schedule->id }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm">
                        <i data-lucide="edit" class="w-3.5 h-3.5 mr-1 text-slate-900"></i> Editar
                    </button>
                    <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" onsubmit="return confirm('¿Eliminar el horario {{ $schedule->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 bg-white hover:bg-red-50 border border-red-200 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-1 text-red-600"></i> Borrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- FLOWBITE MODAL ASIGNAR DEPARTAMENTO -->
            <div id="assignDeptModal-{{ $schedule->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
                        <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                            <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                                <i data-lucide="building-2" class="w-5 h-5 mr-2 text-slate-900"></i>
                                Asignar a Departamento
                            </h3>
                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="assignDeptModal-{{ $schedule->id }}">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form action="{{ route('schedules.assign-department', $schedule) }}" method="POST" class="p-4 md:p-5 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Seleccionar Departamento *</label>
                                <select name="department_id" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                                    <option value="">Seleccione un departamento...</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                <button data-modal-hide="assignDeptModal-{{ $schedule->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Asignar Horario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- FLOWBITE MODAL EDITAR HORARIO -->
            <div id="editScheduleModal-{{ $schedule->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                <div class="relative p-4 w-full max-w-lg max-h-full">
                    <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
                        <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                            <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                                <i data-lucide="edit" class="w-5 h-5 mr-2 text-slate-900"></i>
                                Editar Horario: {{ $schedule->name }}
                            </h3>
                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editScheduleModal-{{ $schedule->id }}">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form action="{{ route('schedules.update', $schedule) }}" method="POST" class="p-4 md:p-5 space-y-4">
                            @csrf
                            @method('PUT')
                            
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Horario *</label>
                                    <input type="text" name="name" required value="{{ $schedule->name }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Tolerancia (min) *</label>
                                    <input type="number" name="tolerance_minutes" required value="{{ $schedule->tolerance_minutes }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-extrabold uppercase text-slate-700">Configuración por Días</label>
                                @foreach($diasSemana as $num => $nombre)
                                    @php
                                        $dayConfig = $schedule->days->where('day_of_week', $num)->first();
                                        $isWorking = $dayConfig ? $dayConfig->is_working_day : true;
                                        $entryTime = $dayConfig && $dayConfig->entry_time ? \Carbon\Carbon::parse($dayConfig->entry_time)->format('H:i') : '08:00';
                                        $exitTime = $dayConfig && $dayConfig->exit_time ? \Carbon\Carbon::parse($dayConfig->exit_time)->format('H:i') : '17:00';
                                    @endphp
                                    <div class="flex items-center space-x-3 p-2 bg-slate-50 border border-slate-200 rounded-xl">
                                        <div class="w-24">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="days[{{ $num }}][is_working_day]" value="1" class="sr-only peer day-toggle" onchange="toggleTimes(this)" {{ $isWorking ? 'checked' : '' }}>
                                                <div class="relative w-9 h-5 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                                                <span class="ms-2 text-xs font-bold text-slate-900">{{ $nombre }}</span>
                                            </label>
                                        </div>
                                        
                                        <div class="flex-1 grid grid-cols-2 gap-2 time-inputs-container {{ $isWorking ? '' : 'opacity-30 pointer-events-none' }}">
                                            <div>
                                                <input type="time" name="days[{{ $num }}][entry_time]" value="{{ $entryTime }}" class="bg-white border border-slate-300 text-slate-900 text-xs rounded-lg focus:ring-black focus:border-black block w-full p-1.5 font-mono">
                                            </div>
                                            <div>
                                                <input type="time" name="days[{{ $num }}][exit_time]" value="{{ $exitTime }}" class="bg-white border border-slate-300 text-slate-900 text-xs rounded-lg focus:ring-black focus:border-black block w-full p-1.5 font-mono">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center space-x-2 pt-2">
                                <input type="checkbox" name="is_default" value="1" id="is_default_edit_{{ $schedule->id }}" class="w-4 h-4 text-black bg-slate-100 border-slate-400 rounded focus:ring-black" {{ $schedule->is_default ? 'checked' : '' }}>
                                <label for="is_default_edit_{{ $schedule->id }}" class="text-xs font-bold text-slate-900">Establecer como Horario por Defecto</label>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                <button data-modal-hide="editScheduleModal-{{ $schedule->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- FLOWBITE MODAL NUEVO HORARIO EN BLANCO Y NEGRO -->
<div id="scheduleModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="clock" class="w-5 h-5 mr-2 text-slate-900"></i>
                    Crear Nuevo Horario
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="scheduleModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="{{ route('schedules.store') }}" method="POST" class="p-4 md:p-5 space-y-4">
                @csrf
                
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Horario *</label>
                        <input type="text" name="name" required placeholder="Ej: Horario de Oficina" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Tolerancia (min) *</label>
                        <input type="number" name="tolerance_minutes" required value="15" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-extrabold uppercase text-slate-700">Configuración por Días</label>
                    @foreach($diasSemana as $num => $nombre)
                        <div class="flex items-center space-x-3 p-2 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="w-24">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="days[{{ $num }}][is_working_day]" value="1" class="sr-only peer day-toggle" onchange="toggleTimes(this)" checked>
                                    <div class="relative w-9 h-5 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                                    <span class="ms-2 text-xs font-bold text-slate-900">{{ $nombre }}</span>
                                </label>
                            </div>
                            
                            <div class="flex-1 grid grid-cols-2 gap-2 time-inputs-container">
                                <div>
                                    <input type="time" name="days[{{ $num }}][entry_time]" value="08:00" class="bg-white border border-slate-300 text-slate-900 text-xs rounded-lg focus:ring-black focus:border-black block w-full p-1.5 font-mono">
                                </div>
                                <div>
                                    <input type="time" name="days[{{ $num }}][exit_time]" value="17:00" class="bg-white border border-slate-300 text-slate-900 text-xs rounded-lg focus:ring-black focus:border-black block w-full p-1.5 font-mono">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" name="is_default" value="1" id="is_default" class="w-4 h-4 text-black bg-slate-100 border-slate-400 rounded focus:ring-black">
                    <label for="is_default" class="text-xs font-bold text-slate-900">Establecer como Horario por Defecto</label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button data-modal-hide="scheduleModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Horario</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleTimes(checkbox) {
        const container = checkbox.closest('.flex').querySelector('.time-inputs-container');
        if (checkbox.checked) {
            container.classList.remove('opacity-30', 'pointer-events-none');
        } else {
            container.classList.add('opacity-30', 'pointer-events-none');
        }
    }
</script>
@endpush
@endsection
