@extends('layouts.app')

@section('title', 'Gestión de Personal - IntalnetAcces')
@section('page-header', 'Gestión de Empleados')
@section('page-sub-header', 'Administración de personal, filtro de huellas faltantes y enrolamiento biométrico ISAPI')

@section('content')
<div class="space-y-6">

    @php
        $missingCount = \App\Models\Employee::where('has_fingerprint', false)->count();
        $registeredCount = \App\Models\Employee::where('has_fingerprint', true)->count();
    @endphp

    <!-- BANNER DE ALERTA: EMPLEADOS SIN HUELLA -->
    @if($missingCount > 0)
        <div class="bg-black text-white p-5 rounded-2xl border border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-white text-black font-extrabold flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-heading font-extrabold text-sm text-white">Atención: Empleados Pendientes de Huella</h3>
                    <p class="text-xs text-slate-300">Hay <strong>{{ $missingCount }} empleado(s)</strong> sin huella registrada. Puedes registrarlos de inmediato para evitar errores de marcación.</p>
                </div>
            </div>

            <a href="{{ route('employees.index', ['filter_fingerprint' => 'missing']) }}" class="bg-white hover:bg-slate-200 text-slate-900 font-extrabold text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center whitespace-nowrap">
                <i data-lucide="filter" class="w-4 h-4 mr-1.5"></i> Ver Pendientes ({{ $missingCount }})
            </a>
        </div>
    @endif

    <!-- Top Action & Filter Bar (Black & White Flowbite Style) -->
    <div class="bw-card p-5 rounded-2xl flex flex-col lg:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto flex-1">
            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nombre, ID o cédula..." class="bg-slate-100 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full pl-10 p-2.5 font-medium placeholder-slate-400">
            </div>

            <!-- Fast Fingerprint Filter Tabs -->
            <select name="filter_fingerprint" class="bg-slate-100 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full sm:w-56 p-2.5 font-semibold">
                <option value="">Todos los Empleados</option>
                <option value="missing" {{ request('filter_fingerprint') == 'missing' ? 'selected' : '' }}>⚠️ Pendientes de Huella ({{ $missingCount }})</option>
                <option value="registered" {{ request('filter_fingerprint') == 'registered' ? 'selected' : '' }}>✔ Con Huella ({{ $registeredCount }})</option>
            </select>

            <button type="submit" class="bg-black hover:bg-slate-800 text-white font-bold rounded-xl text-sm px-4 py-2.5 flex items-center justify-center w-full sm:w-auto shadow-sm">
                <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filtrar
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            @if(\App\Models\Device::count() > 0)
                <!-- Botón Importar Usuarios del Huellero -->
                <form action="{{ route('employees.import') }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="bg-white hover:bg-slate-100 text-slate-900 border border-slate-300 font-bold rounded-xl text-sm px-4 py-2.5 flex items-center justify-center space-x-2 w-full sm:w-auto shadow-sm">
                        <i data-lucide="download-cloud" class="w-4 h-4 text-slate-800"></i>
                        <span>Importar Usuarios del Huellero</span>
                    </button>
                </form>
            @endif

            <!-- Botón Modal Nuevo Empleado (OBLIGATORIO CON HUELLA) -->
            <button type="button" data-modal-target="employeeModal" data-modal-toggle="employeeModal" class="bg-black hover:bg-slate-800 text-white font-extrabold rounded-xl text-sm px-5 py-2.5 flex items-center justify-center space-x-2 shadow-sm w-full sm:w-auto">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>+ Nuevo Empleado (Con Huella)</span>
            </button>
        </div>
    </div>

    <!-- Black & White Corporate Table -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">ID Biométrico</th>
                        <th scope="col" class="px-6 py-4">Empleado</th>
                        <th scope="col" class="px-6 py-4">Departamento / Cargo</th>
                        <th scope="col" class="px-6 py-4 text-center">Estado de Huella Dactilar</th>
                        <th scope="col" class="px-6 py-4 text-center">Estado</th>
                        <th scope="col" class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employees as $employee)
                        <tr class="bg-white hover:bg-slate-50/80 transition-colors border-b border-slate-100 last:border-0">
                            <!-- ID BIOMÉTRICO -->
                            <td class="px-6 py-4.5 font-mono text-xs">
                                <span class="text-slate-400 font-medium">#</span><span class="text-slate-900 font-black text-sm">{{ $employee->employee_no }}</span>
                            </td>

                            <!-- EMPLEADO -->
                            <td class="px-6 py-4.5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-900 to-slate-800 font-extrabold text-white text-[13px] flex items-center justify-center shadow-sm border border-slate-700/10">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 leading-tight tracking-tight">{{ $employee->name }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5 font-semibold flex items-center">
                                            <i data-lucide="contact" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                                            Doc: {{ $employee->document_id ?? 'Sin documento' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- DEPARTAMENTO / CARGO -->
                            <td class="px-6 py-4.5">
                                <div class="space-y-0.5">
                                    <p class="text-slate-900 font-extrabold text-xs flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 mr-1.5"></span>
                                        {{ $employee->department->name ?? 'General' }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 font-bold ml-3">{{ $employee->position ?? 'Sin cargo' }}</p>
                                </div>
                            </td>

                            <!-- ESTADO DE HUELLA DACTILAR -->
                            <td class="px-6 py-4.5 text-center">
                                @if($employee->has_fingerprint)
                                    <span class="bg-black text-white text-[11px] font-black px-3 py-1.5 rounded-full border border-black inline-flex items-center shadow-sm tracking-wide">
                                        <i data-lucide="fingerprint" class="w-3.5 h-3.5 mr-1.5 text-white"></i>
                                        Huella Registrada
                                    </span>
                                @else
                                    <span class="bg-amber-50 text-amber-700 text-[11px] font-black px-3 py-1.5 rounded-full border border-amber-200 inline-flex items-center tracking-wide animate-pulse">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 mr-1.5 text-amber-600"></i>
                                        Pendiente Huella
                                    </span>
                                @endif
                            </td>

                            <!-- ESTADO ACTIVO -->
                            <td class="px-6 py-4.5 text-center">
                                @if($employee->is_active)
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-black px-3 py-1.5 rounded-full inline-flex items-center shadow-sm tracking-wide">
                                        <span class="relative flex h-2 w-2 mr-1.5">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        Activo
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-400 border border-slate-200 text-[11px] font-bold px-3 py-1.5 rounded-full inline-flex items-center">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-6 py-4.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Ver Ficha -->
                                    <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 font-extrabold text-[11px] px-2.5 py-1.5 rounded-lg transition-all shadow-sm" title="Ver perfil del empleado">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Ver</span>
                                    </a>

                                    <!-- Capturar / Cambiar Huella -->
                                    @if(!$employee->has_fingerprint)
                                        <button type="button" onclick="selectDeviceAndCapture('{{ $employee->employee_no }}', '{{ $employee->name }}')" class="inline-flex items-center justify-center gap-1.5 text-white bg-black hover:bg-slate-900 font-extrabold text-[11px] px-2.5 py-1.5 rounded-lg transition-all shadow-sm">
                                            <i data-lucide="fingerprint" class="w-3.5 h-3.5"></i>
                                            <span>Capturar</span>
                                        </button>
                                    @else
                                        <button type="button" onclick="selectDeviceAndCapture('{{ $employee->employee_no }}', '{{ $employee->name }}')" class="inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 font-extrabold text-[11px] px-2.5 py-1.5 rounded-lg transition-all shadow-sm" title="Reenrolar o cambiar huella dactilar">
                                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                                            <span>Huella</span>
                                        </button>
                                    @endif

                                    <!-- Editar Datos -->
                                    <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 font-extrabold text-[11px] px-2.5 py-1.5 rounded-lg transition-all shadow-sm">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Editar</span>
                                    </a>

                                    <!-- Eliminar -->
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('¿Eliminar al empleado {{ $employee->name }}?')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 text-red-600 bg-white hover:bg-red-50 hover:text-red-700 border border-red-200 font-extrabold text-[11px] px-2.5 py-1.5 rounded-lg transition-all shadow-sm">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Borrar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                No hay empleados registrados en esta categoría.<br>
                                <span class="text-xs text-slate-800 font-bold mt-1 block">Haz clic en <strong>"+ Nuevo Empleado (Con Huella)"</strong> arriba para comenzar.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-200 bg-white">
                {{ $employees->links('partials.pagination') }}
            </div>
        @endif
    </div>

</div>

<!-- FLOWBITE MODAL REGISTRAR EMPLEADO (CAPTURA DE HUELLA OBLIGATORIA) -->
<div id="employeeModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="user-plus" class="w-5 h-5 mr-2 text-slate-900"></i>
                    Registrar Nuevo Empleado y Capturar Huella
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="employeeModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <!-- Modal body -->
            <form action="{{ route('employees.store') }}" method="POST" class="p-4 md:p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">ID Biométrico (EmployeeNo) *</label>
                        <input type="text" name="employee_no" required placeholder="Ej: 105" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre Completo *</label>
                        <input type="text" name="name" required placeholder="Ej: Carlos Ramírez" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Cédula / Documento</label>
                        <input type="text" name="document_id" placeholder="Ej: 1098765432" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Departamento</label>
                        <select name="department_id" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                            <option value="">Seleccionar departamento...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Cargo</label>
                        <input type="text" name="position" placeholder="Ej: Operador" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Horario Asignado</label>
                        @php $allSchedules = \App\Models\Schedule::all(); @endphp
                        <select name="schedule_id" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                            <option value="">Seleccionar horario...</option>
                            @foreach($allSchedules as $sch)
                                <option value="{{ $sch->id }}" {{ $sch->is_default ? 'selected' : '' }}>{{ $sch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- SELECT OBLIGATORIO DE UBICACIÓN DEL HUELLERO PARA REGISTRO DE HUELLA -->
                <div class="p-4 rounded-xl bg-slate-100 border border-slate-300 space-y-2">
                    <label class="block text-xs font-extrabold uppercase text-slate-900 flex items-center">
                        <i data-lucide="fingerprint" class="w-4 h-4 mr-1.5 text-black"></i> Select de Huellero donde está el empleado (Obligatorio) *
                    </label>
                    <select name="device_id" required class="bg-white border border-slate-400 text-slate-900 text-sm font-bold rounded-xl focus:ring-black focus:border-black block w-full p-2.5 shadow-sm">
                        <option value="" disabled selected>-- Escoge en qué huellero está parado el empleado --</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}">📍 {{ $dev->name }} - {{ $dev->location }} ({{ $dev->ip_address }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-600 font-medium">Al guardar, el sistema enviará al usuario al huellero seleccionado y activará la lectura dactilar inmediatamente.</p>
                </div>

                <!-- Modal footer -->
                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button data-modal-hide="employeeModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-6 py-2.5 shadow-md">Guardar y Capturar Huella</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FLOWBITE MODAL SELECCIÓN DE HUELLERO Y SONDEO DE CAPTURA -->
<div id="captureModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm transition-opacity">
    <div class="relative p-4 w-full max-w-md max-h-full transition-transform transform scale-100">
        <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden" id="captureModalCard">
            <!-- Decorative Top Gradient -->
            <div class="h-2 w-full bg-gradient-to-r from-slate-800 via-black to-slate-800" id="captureModalGradient"></div>
            
            <div class="p-8 text-center space-y-6">
                <!-- Icon Container with Pulse effect -->
                <div class="relative mx-auto w-24 h-24">
                    <div class="absolute inset-0 bg-slate-200 rounded-full animate-ping opacity-75 hidden" id="captureModalPulse"></div>
                    <div id="captureModalIconContainer" class="relative w-full h-full rounded-2xl bg-gradient-to-tr from-black to-slate-800 text-white shadow-xl flex items-center justify-center transform transition-transform hover:scale-105">
                        <i data-lucide="fingerprint" class="w-12 h-12 text-white" id="captureModalIcon"></i>
                    </div>
                </div>

                <div class="space-y-2">
                    <h3 class="font-heading text-2xl font-extrabold text-slate-900 tracking-tight" id="captureModalTitle">Captura Biométrica</h3>
                    <p class="text-sm text-slate-500 leading-relaxed font-medium" id="captureModalMessage">
                        Selecciona el reloj donde está el empleado para enviar la instrucción por red.
                    </p>
                </div>

                <!-- SELECTOR DE HUELLERO DENTRO DEL MODAL DE CAPTURA -->
                <div class="text-left space-y-2 bg-slate-50 p-5 rounded-2xl border border-slate-200 shadow-inner" id="deviceSelectBox">
                    <label class="block text-xs font-extrabold tracking-wider uppercase text-slate-700 flex items-center">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 mr-1.5 text-black"></i> Ubicación del Huellero
                    </label>
                    <select id="modal_capture_device_id" class="bg-white border-2 border-slate-200 text-slate-900 text-sm font-bold rounded-xl focus:ring-black focus:border-black block w-full p-3 shadow-sm transition-colors cursor-pointer hover:border-slate-300">
                        @foreach($devices as $dev)
                            <option value="{{ $dev->id }}">📍 {{ $dev->name }} - {{ $dev->location }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-center h-6">
                    <span class="bg-slate-100 text-slate-700 text-xs font-bold px-4 py-1.5 rounded-full hidden inline-flex items-center transition-all" id="captureStatusBadge">
                        <span class="relative flex h-2.5 w-2.5 mr-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-black opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-black"></span>
                        </span>
                        Esperando lectura... (30s)
                    </span>
                </div>

                <div class="pt-4 space-y-3">
                    <button type="button" id="startCaptureBtn" onclick="startSelectedDeviceCapture()" class="w-full group text-white bg-black hover:bg-slate-900 font-extrabold rounded-xl text-sm px-5 py-4 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-0.5 flex items-center justify-center space-x-2">
                        <i data-lucide="radio" class="w-5 h-5 group-hover:animate-pulse"></i>
                        <span>Iniciar Lectura Biométrica</span>
                    </button>

                    <button type="button" id="confirmManualBtn" onclick="confirmSuccessManual()" class="w-full text-white bg-slate-800 hover:bg-black font-extrabold rounded-xl text-sm px-5 py-4 shadow-lg transition-all flex items-center justify-center space-x-2 hidden">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span>✔ Ya puse la huella (Confirmar)</span>
                    </button>

                    <button type="button" id="retryBtn" onclick="retryCapture()" class="w-full text-slate-700 bg-white hover:bg-slate-50 font-extrabold rounded-xl text-sm px-5 py-4 shadow-sm border-2 border-slate-200 transition-all flex items-center justify-center space-x-2 hidden">
                        <i data-lucide="rotate-cw" class="w-5 h-5"></i>
                        <span>Reintentar Captura</span>
                    </button>

                    <button type="button" onclick="closeCaptureModal()" class="w-full text-slate-500 hover:text-slate-800 bg-transparent hover:bg-slate-100 font-bold rounded-xl text-sm px-5 py-3 transition-colors">
                        Cancelar y Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
{
    let pollInterval = null;
    let timerTimeout = null;
    let countdownInterval = null;
    let currentEmployeeNo = null;
    let currentEmployeeName = null;
    let secondsLeft = 30;

    window.openEmployeeModal = function() {
        if (window.FlowbiteInstances) {
            const modal = window.FlowbiteInstances.getInstance('Modal', 'employeeModal');
            if (modal) modal.show();
        }
    }

    window.renderModalIcon = function(iconName, classes) {
        const container = document.getElementById('captureModalIconContainer');
        container.innerHTML = `<i data-lucide="${iconName}" class="${classes}"></i>`;
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    window.selectDeviceAndCapture = function(employeeNo, name) {
        clearAllTimers();
        currentEmployeeNo = employeeNo;
        currentEmployeeName = name;

        const captureModal = document.getElementById('captureModal');
        const iconContainer = document.getElementById('captureModalIconContainer');
        const card = document.getElementById('captureModalCard');
        
        document.getElementById('deviceSelectBox').classList.remove('hidden');
        document.getElementById('startCaptureBtn').classList.remove('hidden');
        document.getElementById('confirmManualBtn').classList.add('hidden');
        document.getElementById('retryBtn').classList.add('hidden');
        document.getElementById('captureStatusBadge').classList.add('hidden');
        document.getElementById('captureModalPulse').classList.add('hidden');
        document.getElementById('captureModalGradient').className = "h-2 w-full bg-gradient-to-r from-slate-800 via-black to-slate-800";

        card.className = "relative bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden";
        iconContainer.className = "relative w-full h-full rounded-2xl bg-gradient-to-tr from-black to-slate-800 text-white shadow-xl flex items-center justify-center transform transition-transform hover:scale-105";
        
        renderModalIcon('fingerprint', 'w-12 h-12 text-white');

        document.getElementById('captureModalTitle').textContent = "Captura Biométrica";
        document.getElementById('captureModalTitle').className = "font-heading text-2xl font-extrabold text-slate-900 tracking-tight";
        document.getElementById('captureModalMessage').innerHTML = `Escoge el reloj para <strong>${name}</strong> (#${employeeNo}) para enviar la instrucción por red.`;

        captureModal.classList.remove('hidden');
        captureModal.classList.add('flex');
    }

    window.startSelectedDeviceCapture = function() {
        const selectedDeviceId = document.getElementById('modal_capture_device_id').value;
        const employeeNo = currentEmployeeNo;
        const name = currentEmployeeName;

        secondsLeft = 30;

        document.getElementById('deviceSelectBox').classList.add('hidden');
        document.getElementById('startCaptureBtn').classList.add('hidden');
        document.getElementById('confirmManualBtn').classList.remove('hidden');
        document.getElementById('captureStatusBadge').classList.remove('hidden');
        document.getElementById('captureModalPulse').classList.remove('hidden');

        document.getElementById('captureModalTitle').textContent = "Esperando Lectura...";
        document.getElementById('captureModalMessage').innerHTML = `Instrucción enviada.<br>Dile a <strong>${name}</strong> (#${employeeNo}) que coloque su dedo sobre el lector.`;
        document.getElementById('captureStatusBadge').innerHTML = `<span class="relative flex h-2.5 w-2.5 mr-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-black opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-black"></span></span> Esperando lectura... (${secondsLeft}s)`;
        document.getElementById('captureStatusBadge').className = "bg-slate-100 text-slate-700 text-xs font-bold px-4 py-1.5 rounded-full inline-flex items-center transition-all";

        // Enviar orden ISAPI al huellero seleccionado
        fetch("{{ route('employees.capture-fingerprint') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                employee_no: employeeNo,
                name: name,
                device_id: selectedDeviceId
            })
        });

        countdownInterval = setInterval(() => {
            secondsLeft--;
            if (secondsLeft > 0) {
                document.getElementById('captureStatusBadge').innerHTML = `<span class="relative flex h-2.5 w-2.5 mr-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-black opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-black"></span></span> Esperando lectura... (${secondsLeft}s)`;
            } else {
                clearInterval(countdownInterval);
            }
        }, 1000);

        pollInterval = setInterval(() => {
            fetch("{{ route('employees.check-fingerprint-status') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    employee_no: employeeNo,
                    device_id: selectedDeviceId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.registered === true) {
                    confirmSuccessVisual(name, employeeNo);
                }
            });
        }, 2000);

        timerTimeout = setTimeout(() => {
            if (pollInterval) {
                clearAllTimers();
                showFailureVisual(name, employeeNo);
            }
        }, 30000);
    }

    window.retryCapture = function() {
        if (currentEmployeeNo && currentEmployeeName) {
            selectDeviceAndCapture(currentEmployeeNo, currentEmployeeName);
        }
    }

    window.confirmSuccessManual = function() {
        confirmSuccessVisual(currentEmployeeName || 'Empleado', currentEmployeeNo || '');
    }

    window.confirmSuccessVisual = function(name, employeeNo) {
        clearAllTimers();

        fetch("{{ route('employees.confirm-fingerprint') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                employee_no: employeeNo
            })
        });

        const iconContainer = document.getElementById('captureModalIconContainer');
        const card = document.getElementById('captureModalCard');

        document.getElementById('captureModalPulse').classList.add('hidden');
        document.getElementById('captureModalGradient').className = "h-2 w-full bg-gradient-to-r from-emerald-400 to-emerald-600";
        card.className = "relative bg-white rounded-3xl shadow-2xl border-2 border-emerald-500 overflow-hidden transform transition-all scale-105";
        iconContainer.className = "relative w-full h-full rounded-2xl bg-gradient-to-tr from-emerald-500 to-emerald-700 text-white shadow-xl flex items-center justify-center transform transition-transform animate-bounce";
        
        renderModalIcon('check-circle', 'w-12 h-12 text-white');

        document.getElementById('captureModalTitle').textContent = "¡Huella Registrada!";
        document.getElementById('captureModalTitle').className = "font-heading text-2xl font-extrabold text-emerald-600 tracking-tight";
        document.getElementById('captureModalMessage').innerHTML = `<strong class="text-slate-900 text-sm font-extrabold">El huellero ha grabado exitosamente la huella de ${name} (#${employeeNo}).</strong><br><span class="text-xs text-slate-500 mt-2 block">Sincronizando...</span>`;
        document.getElementById('captureStatusBadge').innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5 mr-1.5"></i> Confirmado por ISAPI`;
        document.getElementById('captureStatusBadge').className = "bg-emerald-50 text-emerald-700 text-xs font-bold px-4 py-1.5 rounded-full inline-flex items-center transition-all";

        document.getElementById('confirmManualBtn').classList.add('hidden');

        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    window.showFailureVisual = function(name, employeeNo) {
        const iconContainer = document.getElementById('captureModalIconContainer');
        const card = document.getElementById('captureModalCard');
        const retryBtn = document.getElementById('retryBtn');

        retryBtn.classList.remove('hidden');
        document.getElementById('confirmManualBtn').classList.add('hidden');

        document.getElementById('captureModalPulse').classList.add('hidden');
        document.getElementById('captureModalGradient').className = "h-2 w-full bg-gradient-to-r from-red-400 to-red-600";
        card.className = "relative bg-white rounded-3xl shadow-2xl border-2 border-red-500 overflow-hidden";
        iconContainer.className = "relative w-full h-full rounded-2xl bg-gradient-to-tr from-red-500 to-red-700 text-white shadow-xl flex items-center justify-center";
        
        renderModalIcon('x-circle', 'w-12 h-12 text-white');

        document.getElementById('captureModalTitle').textContent = "Tiempo Agotado";
        document.getElementById('captureModalTitle').className = "font-heading text-2xl font-extrabold text-red-600 tracking-tight";
        document.getElementById('captureModalMessage').innerHTML = `<strong class="text-slate-900 text-sm font-bold">No se detectó la lectura para ${name} (#${employeeNo}).</strong><br><span class="text-xs text-slate-500 mt-2 block">Asegúrate de presionar el dedo bien plano sobre el lector.</span>`;
        document.getElementById('captureStatusBadge').innerHTML = `<i data-lucide="x" class="w-3.5 h-3.5 mr-1.5"></i> Lectura Expirada`;
        document.getElementById('captureStatusBadge').className = "bg-red-50 text-red-700 text-xs font-bold px-4 py-1.5 rounded-full inline-flex items-center transition-all";
    }

    window.clearAllTimers = function() {
        if (pollInterval) clearInterval(pollInterval);
        if (countdownInterval) clearInterval(countdownInterval);
        if (timerTimeout) clearTimeout(timerTimeout);
        pollInterval = null;
        countdownInterval = null;
        timerTimeout = null;
    }

    window.closeCaptureModal = function() {
        clearAllTimers();
        const captureModal = document.getElementById('captureModal');
        captureModal.classList.add('hidden');
        captureModal.classList.remove('flex');
    };
}
</script>
@endpush
@endsection
