@extends('layouts.app')

@section('title', 'Editar Empleado - Control de Asistencia')
@section('page-header', 'Editar Empleado')
@section('page-sub-header', 'Modifica información o cambia la asignación de horario')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bw-card p-6 md:p-8 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center">
                <i data-lucide="edit" class="w-4 h-4 mr-2 text-black"></i>
                Empleado: {{ $employee->name }}
            </h3>
            <a href="{{ route('employees.show', $employee) }}" class="text-xs font-bold text-slate-500 hover:text-black transition-colors flex items-center">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 mr-1"></i> Volver al Perfil
            </a>
        </div>

        <!-- Formulario -->
        <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- ID Biométrico -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">ID Biométrico *</label>
                    <input type="text" name="employee_no" value="{{ old('employee_no', $employee->employee_no) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Nombre Completo -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Nombre Completo *</label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Cédula / Documento -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Cédula / Documento ID</label>
                    <input type="text" name="document_id" value="{{ old('document_id', $employee->document_id) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Departamento -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Departamento</label>
                    <select name="department_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                        <option value="">Seleccionar departamento...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cargo / Posición -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Cargo / Posición</label>
                    <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Horario de Trabajo -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Horario Asignado</label>
                    <select name="schedule_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                        <option value="">Sin horario específico</option>
                        @foreach($schedules as $schedule)
                            <option value="{{ $schedule->id }}" {{ $currentSchedule && $currentSchedule->id == $schedule->id ? 'selected' : '' }}>
                                {{ $schedule->name }} ({{ $schedule->formatted_summary }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Estado Activo/Inactivo -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Estado de Empleado</label>
                    <select name="is_active" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                        <option value="1" {{ $employee->is_active ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ !$employee->is_active ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="pt-5 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('employees.show', $employee) }}" class="text-xs font-bold text-slate-500 hover:text-black transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-5 py-2.5 bg-black hover:bg-slate-800 text-white text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-md">
                    Actualizar Empleado
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
