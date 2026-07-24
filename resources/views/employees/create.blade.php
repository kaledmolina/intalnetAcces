@extends('layouts.app')

@section('title', 'Nuevo Empleado - Control de Asistencia')
@section('page-header', 'Registrar Nuevo Empleado')
@section('page-sub-header', 'Crea un perfil de empleado para vincular marcaciones del biométrico')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bw-card p-6 md:p-8 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center">
                <i data-lucide="user-plus" class="w-4 h-4 mr-2 text-black"></i>
                Datos del Empleado
            </h3>
            <a href="{{ route('employees.index') }}" class="text-xs font-bold text-slate-500 hover:text-black transition-colors flex items-center">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 mr-1"></i> Cancelar y Volver
            </a>
        </div>

        <!-- Formulario -->
        <form action="{{ route('employees.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- ID Biométrico / Empleado No -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">ID Biométrico (EmployeeNo) *</label>
                    <input type="text" name="employee_no" value="{{ old('employee_no') }}" required placeholder="Ej: 101" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                    <p class="text-[10px] text-slate-400 mt-1 font-semibold">Debe coincidir con el código en el huellero.</p>
                </div>

                <!-- Nombre Completo -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Nombre Completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Carlos Mendoza" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Cédula / Documento -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Cédula / Documento ID</label>
                    <input type="text" name="document_id" value="{{ old('document_id') }}" placeholder="Ej: 1098765432" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Departamento -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Departamento</label>
                    <select name="department_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                        <option value="">Seleccionar departamento...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cargo / Posición -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Cargo / Posición</label>
                    <input type="text" name="position" value="{{ old('position') }}" placeholder="Ej: Supervisor" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Horario de Trabajo -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Horario Asignado</label>
                    <select name="schedule_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                        <option value="">Seleccionar horario...</option>
                        @foreach($schedules as $schedule)
                            <option value="{{ $schedule->id }}" {{ $schedule->is_default ? 'selected' : '' }}>
                                {{ $schedule->name }} ({{ $schedule->formatted_summary }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Teléfono / Celular</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Ej: 3001234567" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>

                <!-- Correo Electrónico -->
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1.5">Correo Electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="correo@empresa.com" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-bold placeholder-slate-400 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all shadow-sm">
                </div>
            </div>

            <!-- Botones -->
            <div class="pt-5 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('employees.index') }}" class="text-xs font-bold text-slate-500 hover:text-black transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-5 py-2.5 bg-black hover:bg-slate-800 text-white text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-md">
                    Guardar Empleado
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
