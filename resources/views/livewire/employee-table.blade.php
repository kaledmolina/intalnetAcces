<div class="space-y-6">

    <!-- Top Action & Filter Bar (Livewire Real-time Search) -->
    <div class="bw-card p-5 rounded-2xl flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-4 bg-white border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full xl:w-auto flex-1">
            <!-- Realtime Search Input -->
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Escribe para buscar nombre, ID o cédula..." class="bg-slate-100 border border-slate-300 text-slate-900 text-xs rounded-xl focus:ring-black focus:border-black block w-full pl-9 p-2.5 font-medium placeholder-slate-400 transition-colors">
            </div>

            <!-- Department Filter -->
            <select wire:model.live="departmentId" class="bg-slate-100 border border-slate-300 text-slate-900 text-xs rounded-xl focus:ring-black focus:border-black block w-full sm:w-56 p-2.5 font-semibold">
                <option value="">Todos los Departamentos</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2.5 shrink-0 w-full xl:w-auto">
            @if($deviceCount > 0)
                <!-- Botón Importar Usuarios del Huellero -->
                <form action="{{ route('employees.import') }}" method="POST" class="w-full sm:w-auto inline-flex">
                    @csrf
                    <button type="submit" class="btn-hover-grow bg-black hover:bg-slate-800 text-white font-extrabold rounded-xl text-xs px-4 py-2.5 flex items-center justify-center space-x-2 w-full sm:w-auto shadow-md whitespace-nowrap">
                        <i data-lucide="download-cloud" class="w-4 h-4 text-white"></i>
                        <span>Importar Usuarios del Huellero</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Black & White Corporate Table -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4.5">ID Biométrico</th>
                        <th scope="col" class="px-6 py-4.5">Empleado</th>
                        <th scope="col" class="px-6 py-4.5">Departamento / Cargo</th>
                        <th scope="col" class="px-6 py-4.5 text-center">Estado</th>
                        <th scope="col" class="px-6 py-4.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employees as $employee)
                        <tr class="bg-white hover:bg-slate-50/80 transition-colors border-b border-slate-100 last:border-0">
                            <!-- ID BIOMÉTRICO -->
                            <td class="px-6 py-5 font-mono text-xs whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-900 font-black text-xs px-3 py-1.5 rounded-xl border border-slate-200 inline-flex items-center">
                                    <span class="text-slate-400 font-medium mr-0.5">#</span>{{ $employee->employee_no }}
                                </span>
                            </td>

                            <!-- EMPLEADO -->
                            <td class="px-6 py-5">
                                <div class="flex items-center space-x-3.5">
                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-slate-900 to-slate-800 font-extrabold text-white text-xs flex items-center justify-center shadow-sm border border-slate-700/10 shrink-0">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-extrabold text-slate-900 text-sm leading-snug tracking-tight">{{ $employee->name }}</p>
                                        <p class="text-xs text-slate-500 font-semibold flex items-center">
                                            <i data-lucide="contact" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                                            Doc: {{ $employee->document_id ?? 'Sin documento' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- DEPARTAMENTO / CARGO -->
                            <td class="px-6 py-5">
                                <div class="space-y-1">
                                    <p class="text-slate-900 font-extrabold text-xs flex items-center">
                                        <span class="w-2 h-2 rounded-full bg-black mr-2 shrink-0"></span>
                                        {{ $employee->department->name ?? 'General' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-bold ml-4">{{ $employee->position ?? 'Sin cargo' }}</p>
                                </div>
                            </td>

                            <!-- ESTADO ACTIVO -->
                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                @if($employee->is_active)
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-black px-3.5 py-1.5 rounded-full inline-flex items-center shadow-xs tracking-wide">
                                        <span class="relative flex h-2 w-2 mr-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        Activo
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-500 border border-slate-200 text-xs font-bold px-3.5 py-1.5 rounded-full inline-flex items-center">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-6 py-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Ver Ficha -->
                                    <a href="{{ route('employees.show', $employee) }}" class="btn-hover-grow inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-300 font-extrabold text-xs px-3 py-2 rounded-xl transition-all shadow-xs" title="Ver perfil del empleado">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-700"></i>
                                        <span>Ver</span>
                                    </a>

                                    <!-- Editar Datos -->
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn-hover-grow inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-300 font-extrabold text-xs px-3 py-2 rounded-xl transition-all shadow-xs">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-700"></i>
                                        <span>Editar</span>
                                    </a>

                                    <!-- Eliminar -->
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('¿Eliminar al empleado {{ $employee->name }}?')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-hover-grow inline-flex items-center justify-center gap-1.5 text-red-600 bg-white hover:bg-red-50 hover:text-red-700 border border-red-200 font-extrabold text-xs px-3 py-2 rounded-xl transition-all shadow-xs">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-600"></i>
                                            <span>Borrar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                No hay empleados registrados que coincidan con la búsqueda.<br>
                                <span class="text-xs text-slate-800 font-bold mt-1 block">Haz clic en <strong>"Importar Usuarios del Huellero"</strong> arriba para sincronizar el personal registrado en los equipos.</span>
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
