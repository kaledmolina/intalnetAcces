@extends('layouts.app')

@section('title', 'Dashboard - IntalnetAcces')
@section('page-header', 'Dashboard de Asistencia')
@section('page-sub-header', 'Monitoreo corporativo en tiempo real e integración con huelleros Hikvision ISAPI')

@section('content')
<div class="space-y-6">

    <!-- Black & White High Contrast KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Empleados Totales -->
        <div id="card-total" onclick="showKpiDetails('total')" class="kpi-card cursor-pointer bw-card bw-card-hover p-6 rounded-2xl space-y-3 bg-white border border-slate-200 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Total Personal</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalEmployees }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Empleados registrados</span>
            </div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-black h-full w-full"></div>
            </div>
        </div>

        <!-- Presentes Hoy -->
        <div id="card-present" onclick="showKpiDetails('present')" class="kpi-card cursor-pointer bw-card bw-card-hover p-6 rounded-2xl space-y-3 bg-white border border-slate-200 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Presentes Hoy</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $presentCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Entradas verificadas</span>
            </div>
            @php $presentPercent = $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100) : 0; @endphp
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-black h-full rounded-full transition-all duration-500" style="width: {{ $presentPercent }}%"></div>
            </div>
        </div>

        <!-- Tardanzas Hoy -->
        <div id="card-late" onclick="showKpiDetails('late')" class="kpi-card cursor-pointer bw-card bw-card-hover p-6 rounded-2xl space-y-3 bg-white border border-slate-200 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Tardanzas Hoy</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $lateTodayCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Marcaciones tras tolerancia</span>
            </div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-slate-700 h-full rounded-full" style="width: {{ min(100, $lateTodayCount * 20) }}%"></div>
            </div>
        </div>

        <!-- Pendientes -->
        <div id="card-absent" onclick="showKpiDetails('absent')" class="kpi-card cursor-pointer bw-card bw-card-hover p-6 rounded-2xl space-y-3 bg-white border border-slate-200 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Sin Registrar</span>
                <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                    <i data-lucide="user-minus" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <span class="font-heading text-3xl font-extrabold text-slate-900 tracking-tight">{{ $absentCount }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Pendientes de ingreso</span>
            </div>
            @php $absentPercent = $totalEmployees > 0 ? round(($absentCount / $totalEmployees) * 100) : 0; @endphp
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-slate-400 h-full rounded-full transition-all duration-500" style="width: {{ $absentPercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN INTERACTIVA: DETALLE DE PERSONAL SEGÚN KPI SELECCIONADO -->
    <div id="kpi-details-section" class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200 hidden transition-all duration-300">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="font-heading text-sm font-black text-slate-900 uppercase tracking-wider flex items-center">
                    <i id="kpi-details-icon" data-lucide="users" class="w-4 h-4 mr-2 text-black"></i>
                    <span id="kpi-details-title">Listado de Personal</span>
                </h3>
                <p id="kpi-details-subtitle" class="text-[11px] text-slate-500 font-semibold mt-0.5">Mostrando personal según la tarjeta seleccionada</p>
            </div>
            <button onclick="closeKpiDetails()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3">Empleado</th>
                        <th scope="col" class="px-6 py-3">ID Biométrico</th>
                        <th scope="col" class="px-6 py-3">Departamento / Cargo</th>
                        <th scope="col" class="px-6 py-3">Estado de Hoy</th>
                        <th scope="col" class="px-6 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody id="kpi-details-body" class="divide-y divide-slate-200">
                    <!-- Dinámico -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- COMPONENTE REACT: GRÁFICO DE TENDENCIA -->
    <div id="react-attendance-chart" data-chart='@json($chartData)'></div>

    <!-- COMPONENTE REACT: ESTADO DE DISPOSITIVOS -->
    <div id="react-device-status" data-devices='@json($devicesData)'></div>

    <!-- Black & White Corporate Employees Table with Pagination -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-heading text-base font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="users" class="w-4 h-4 mr-2 text-slate-900"></i>
                    Listado General de Personal
                </h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Visualización y control de empleados activos</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Empleado</th>
                        <th scope="col" class="px-6 py-3.5">ID Biométrico</th>
                        <th scope="col" class="px-6 py-3.5">Departamento / Cargo</th>
                        <th scope="col" class="px-6 py-3.5">Estado de Hoy</th>
                        <th scope="col" class="px-6 py-3.5 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employeesPaginated as $emp)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-black font-extrabold text-white text-xs flex items-center justify-center">
                                        {{ strtoupper(substr($emp->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 leading-tight">
                                            {{ $emp->name }}
                                        </p>
                                        <p class="text-xs text-slate-400 font-semibold mt-0.5">{{ $emp->position ?? 'Sin cargo' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-bold text-slate-700">
                                #{{ $emp->employee_no }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 font-semibold">
                                {{ $emp->department->name ?? 'Sin departamento' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($emp->today_punch_time)
                                    @if($emp->today_is_late)
                                        <span class="bg-slate-200 text-slate-900 text-xs font-extrabold px-2.5 py-1 rounded-full border border-slate-400 inline-flex items-center">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 mr-1"></i>
                                            Presente ({{ $emp->today_punch_time }} - Tarde)
                                        </span>
                                    @else
                                        <span class="bg-black text-white text-xs font-extrabold px-2.5 py-1 rounded-full border border-black inline-flex items-center shadow-sm">
                                            <i data-lucide="check" class="w-3.5 h-3.5 mr-1 text-white"></i>
                                            Presente ({{ $emp->today_punch_time }})
                                        </span>
                                    @endif
                                @else
                                    <span class="bg-slate-100 text-slate-500 text-xs font-bold px-2.5 py-1 rounded-full border border-slate-200">
                                        Sin Registrar
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('employees.show', $emp) }}" class="inline-flex items-center justify-center p-1.5 bg-black hover:bg-slate-800 text-white rounded-lg transition-colors shadow-sm" title="Ver Historial">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-medium">
                                <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                No hay empleados registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employeesPaginated->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $employeesPaginated->links('partials.pagination') }}
            </div>
        @endif
    </div>

</div>

<!-- FLOWBITE MODAL NUEVO EMPLEADO EN BLANCO Y NEGRO -->
<div id="employeeModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-xl max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="user-plus" class="w-5 h-5 mr-2 text-slate-900"></i>
                    Registrar Nuevo Empleado
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="employeeModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
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
                        <input type="text" name="department" placeholder="Ej: Logística" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button data-modal-hide="employeeModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
{
    // Datos interactivos inyectados
    const totalEmployeesData = @json($totalEmployeesList);
    const presentEmployeesData = @json($presentEmployeesList);
    const lateEmployeesData = @json($lateEmployeesList);
    const absentEmployeesData = @json($absentEmployeesList);

    window.showKpiDetails = function(type) {
        const section = document.getElementById('kpi-details-section');
        const titleEl = document.getElementById('kpi-details-title');
        const iconEl = document.getElementById('kpi-details-icon');
        const bodyEl = document.getElementById('kpi-details-body');
        
        let data = [];
        let title = '';
        let iconName = 'users';
        let stateBadgeGenerator = () => '';

        // Remover clases activas de todas las tarjetas
        document.querySelectorAll('.kpi-card').forEach(card => {
            card.classList.remove('ring-2', 'ring-black', 'border-black');
        });

        if (type === 'total') {
            data = totalEmployeesData;
            title = 'Total Personal Registrado';
            iconName = 'users';
            document.getElementById('card-total').classList.add('ring-2', 'ring-black', 'border-black');
            stateBadgeGenerator = (emp) => `<span class="bg-slate-100 text-slate-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-slate-200">Activo</span>`;
        } else if (type === 'present') {
            data = presentEmployeesData;
            title = 'Personal Presente Hoy';
            iconName = 'user-check';
            document.getElementById('card-present').classList.add('ring-2', 'ring-black', 'border-black');
            stateBadgeGenerator = (emp) => {
                if (emp.today_is_late) {
                    return `<span class="bg-slate-200 text-slate-900 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-slate-400 inline-flex items-center">
                        <i data-lucide="clock" class="w-3 h-3 mr-1 text-slate-700"></i> Presente (${emp.today_punch_time} - Tarde)
                    </span>`;
                } else {
                    return `<span class="bg-black text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-black inline-flex items-center shadow-sm">
                        <i data-lucide="check" class="w-3 h-3 mr-1 text-white"></i> Presente (${emp.today_punch_time})
                    </span>`;
                }
            };
        } else if (type === 'late') {
            data = lateEmployeesData;
            title = 'Personal con Tardanza Hoy';
            iconName = 'clock';
            document.getElementById('card-late').classList.add('ring-2', 'ring-black', 'border-black');
            stateBadgeGenerator = (emp) => `<span class="bg-slate-200 text-slate-900 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-slate-400 inline-flex items-center">
                <i data-lucide="clock" class="w-3 h-3 mr-1 text-slate-700"></i> Entrada: ${emp.today_punch_time} (+${emp.today_late_minutes} min)
            </span>`;
        } else if (type === 'absent') {
            data = absentEmployeesData;
            title = 'Personal Sin Registrar Hoy (Falta)';
            iconName = 'user-minus';
            document.getElementById('card-absent').classList.add('ring-2', 'ring-black', 'border-black');
            stateBadgeGenerator = (emp) => `<span class="bg-slate-200 text-slate-900 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-slate-400">Sin marcación de entrada</span>`;
        }

        // Renderizar filas
        bodyEl.innerHTML = '';
        if (data.length === 0) {
            bodyEl.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 font-bold">
                        No hay empleados registrados en esta categoría.
                    </td>
                </tr>
            `;
        } else {
            data.forEach(emp => {
                const tr = document.createElement('tr');
                tr.className = 'bg-white hover:bg-slate-50 transition-colors';
                
                const initials = emp.name.substring(0, 2).toUpperCase();
                const viewUrl = `/employees/${emp.id}`;

                tr.innerHTML = `
                    <td class="px-6 py-3.5">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-xl bg-black font-extrabold text-white text-[11px] flex items-center justify-center shadow-sm">
                                ${initials}
                            </div>
                            <div>
                                <p class="font-extrabold text-slate-900 leading-tight">${emp.name}</p>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">${emp.position || 'Sin cargo'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3.5 whitespace-nowrap font-mono text-xs font-bold text-slate-700">
                        #${emp.employee_no}
                    </td>
                    <td class="px-6 py-3.5 text-xs text-slate-600 font-semibold">
                        ${emp.department || 'Sin departamento'}
                    </td>
                    <td class="px-6 py-3.5 whitespace-nowrap">
                        ${stateBadgeGenerator(emp)}
                    </td>
                    <td class="px-6 py-3.5 whitespace-nowrap text-right">
                        <a href="${viewUrl}" class="inline-flex items-center justify-center p-1.5 bg-black hover:bg-slate-800 text-white rounded-lg transition-colors shadow-sm" title="Ver Historial">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                        </a>
                    </td>
                `;
                bodyEl.appendChild(tr);
            });
        }

        // Actualizar icono
        iconEl.setAttribute('data-lucide', iconName);
        titleEl.textContent = title;
        
        // Mostrar sección
        section.classList.remove('hidden');
        
        // Re-crear iconos
        if (window.lucide) {
            lucide.createIcons();
        }

        // Deslizar suavemente a la tabla de detalles
        section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.closeKpiDetails = function() {
        document.getElementById('kpi-details-section').classList.add('hidden');
        document.querySelectorAll('.kpi-card').forEach(card => {
            card.classList.remove('ring-2', 'ring-black', 'border-black');
        });
    };
}
</script>
@endpush
@endsection
