@extends('layouts.app')

@section('title', 'Departamentos - IntalnetAcces')
@section('page-header', 'Gestión de Departamentos')
@section('page-sub-header', 'Administración de áreas y asignación de horarios por departamento')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="bw-card p-5 rounded-2xl flex items-center justify-between shadow-sm bg-white border border-slate-200">
        <div class="flex items-center space-x-3">
            <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                <i data-lucide="building-2" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-extrabold text-lg text-slate-900">Departamentos</h3>
                <p class="text-xs text-slate-500 font-medium">Listado de áreas de la empresa</p>
            </div>
        </div>

        <button type="button" data-modal-target="departmentModal" data-modal-toggle="departmentModal" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 flex items-center space-x-2 shadow-md transition-colors">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>+ Nuevo Depto</span>
        </button>
    </div>

    <!-- Departments Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($departments as $department)
            <div class="bw-card p-6 rounded-2xl flex flex-col justify-between shadow-sm bg-white border border-slate-200 hover:shadow-md transition-shadow">
                <div>
                    <div class="flex items-start justify-between border-b border-slate-200 pb-4 mb-4">
                        <div>
                            <h3 class="font-heading font-extrabold text-lg text-slate-900 mb-1">{{ $department->name }}</h3>
                            <div class="flex items-center text-xs text-slate-500 font-medium">
                                <i data-lucide="users" class="w-3.5 h-3.5 mr-1.5"></i>
                                {{ $department->employees_count }} empleado(s)
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="block text-[10px] font-extrabold uppercase text-slate-500 tracking-wider mb-1">Horario Asignado</span>
                            @if($department->schedule)
                                <div class="flex items-center space-x-2 text-slate-900">
                                    <i data-lucide="clock" class="w-4 h-4 text-slate-700"></i>
                                    <span class="font-bold text-sm">{{ $department->schedule->name }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 font-medium">{{ $department->schedule->formatted_summary }}</p>
                            @else
                                <div class="flex items-center space-x-2 text-slate-500">
                                    <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                                    <span class="font-bold text-sm italic">Sin horario específico</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">Usa horario por defecto de la empresa o asignación individual.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-4 flex items-center justify-end space-x-2 border-t border-slate-100">
                    <button type="button" data-modal-target="assignEmployeesModal-{{ $department->id }}" data-modal-toggle="assignEmployeesModal-{{ $department->id }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm transition-colors">
                        <i data-lucide="users" class="w-3.5 h-3.5 mr-1"></i> Empleados
                    </button>
                    <button type="button" data-modal-target="editDepartmentModal-{{ $department->id }}" data-modal-toggle="editDepartmentModal-{{ $department->id }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm transition-colors">
                        <i data-lucide="edit" class="w-3.5 h-3.5 mr-1"></i> Editar
                    </button>
                    <form action="{{ route('departments.destroy', $department) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar el departamento {{ $department->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 bg-white hover:bg-red-50 border border-red-200 font-extrabold rounded-lg text-xs px-3 py-1.5 flex items-center shadow-sm transition-colors {{ $department->employees_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $department->employees_count > 0 ? 'disabled title="No se puede eliminar porque tiene empleados"' : '' }}>
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-1"></i> Borrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Editar -->
            <div id="editDepartmentModal-{{ $department->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
                        <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                            <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                                <i data-lucide="edit" class="w-5 h-5 mr-2"></i> Editar Departamento
                            </h3>
                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editDepartmentModal-{{ $department->id }}">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form action="{{ route('departments.update', $department) }}" method="POST" class="p-5 space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Departamento *</label>
                                <input type="text" name="name" required value="{{ $department->name }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                            </div>
                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <button data-modal-hide="editDepartmentModal-{{ $department->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Asignar Empleados -->
            <div id="assignEmployeesModal-{{ $department->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                <div class="relative p-4 w-full max-w-lg max-h-full">
                    <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
                        <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                            <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                                <i data-lucide="users" class="w-5 h-5 mr-2"></i> Asignar Empleados
                            </h3>
                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="assignEmployeesModal-{{ $department->id }}">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form action="{{ route('departments.assign-employees', $department) }}" method="POST" class="p-5 space-y-4">
                            @csrf
                            <p class="text-xs text-slate-500 font-bold mb-2">Selecciona los empleados que pertenecen a <strong>{{ $department->name }}</strong>:</p>
                            
                            <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl bg-slate-50 p-2 space-y-1">
                                @forelse($employees as $emp)
                                    <label class="flex items-center p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-slate-200">
                                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="w-4 h-4 text-black bg-white border-slate-300 rounded focus:ring-black" {{ $emp->department_id == $department->id ? 'checked' : '' }}>
                                        <span class="ml-3 text-sm font-bold text-slate-900">{{ $emp->name }}</span>
                                        <span class="ml-auto text-[10px] text-slate-400 font-mono">#{{ $emp->employee_no }}</span>
                                    </label>
                                @empty
                                    <div class="text-center p-4 text-xs text-slate-500 font-bold">No hay empleados registrados.</div>
                                @endforelse
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                                <button data-modal-hide="assignEmployeesModal-{{ $department->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Asignación</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- Modal Nuevo -->
<div id="departmentModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
            <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="building-2" class="w-5 h-5 mr-2"></i> Nuevo Departamento
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="departmentModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="{{ route('departments.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Departamento *</label>
                    <input type="text" name="name" required placeholder="Ej: Recursos Humanos" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                </div>
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button data-modal-hide="departmentModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Crear Depto</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
