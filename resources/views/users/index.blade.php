@extends('layouts.app')

@section('title', 'Usuarios - IntalnetAcces')
@section('page-header', 'Gestión de Cuentas SaaS y Usuarios')
@section('page-sub-header', 'Control de credenciales, activación de inquilinos y privilegios de SuperAdmin')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="space-y-1">
            <h3 class="font-heading font-extrabold text-sm text-slate-900">Cuentas Registradas en la Plataforma</h3>
            <p class="text-xs text-slate-500 font-semibold">Administra todas las empresas e inquilinos registrados en el sistema SaaS</p>
        </div>
        <button data-modal-target="userModal" data-modal-toggle="userModal" onclick="openCreateModal()" class="btn-hover-grow flex items-center justify-center space-x-2 bg-black hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl shadow-md">
            <i data-lucide="user-plus" class="w-4 h-4 text-white"></i>
            <span>Nuevo Usuario / Empresa</span>
        </button>
    </div>

    <!-- Users Table -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Nombre / Empresa</th>
                        <th scope="col" class="px-6 py-3.5">Correo Electrónico</th>
                        <th scope="col" class="px-6 py-3.5">Sede / Ubicación</th>
                        <th scope="col" class="px-6 py-3.5 text-center">Empleados</th>
                        <th scope="col" class="px-6 py-3.5">Rol / Privilegio</th>
                        <th scope="col" class="px-6 py-3.5 text-center">Estado Acceso</th>
                        <th scope="col" class="px-6 py-3.5">Registro</th>
                        <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($users as $user)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <!-- Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-black font-extrabold text-white text-xs flex items-center justify-center shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 leading-tight">{{ $user->name }}</p>
                                        @if($user->id === Auth::id())
                                            <span class="bg-slate-100 text-slate-800 text-[9px] font-black px-1.5 py-0.5 rounded border border-slate-300 mt-1 inline-block uppercase">Tu Cuenta</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <!-- Email -->
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-700">
                                {{ $user->email }}
                            </td>
                            <!-- Sede / Location -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->sedeRelation)
                                    <span class="bg-black text-white text-xs font-extrabold px-2.5 py-1 rounded-lg border border-black inline-flex items-center shadow-sm">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 mr-1 text-white"></i> {{ $user->sedeRelation->name }}
                                        <span class="ml-1.5 text-[9px] font-mono font-bold text-slate-300">[{{ $user->sedeRelation->code }}]</span>
                                    </span>
                                @elseif($user->sede)
                                    <span class="bg-slate-100 text-slate-800 text-xs font-extrabold px-2.5 py-1 rounded-lg border border-slate-200 inline-flex items-center">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 mr-1 text-slate-700"></i> {{ $user->sede }}
                                    </span>
                                @else
                                    <button data-modal-target="assignSedeModal-{{ $user->id }}" data-modal-toggle="assignSedeModal-{{ $user->id }}" class="bg-red-50 text-red-600 hover:bg-red-100 text-[11px] font-extrabold px-2.5 py-1 rounded-lg border border-red-200 inline-flex items-center transition-colors shadow-sm" title="Haz clic para asignar una sede">
                                        <i data-lucide="plus-circle" class="w-3.5 h-3.5 mr-1"></i> Sin Sede (Asignar)
                                    </button>
                                @endif
                            </td>
                            <!-- Employees count -->
                            <td class="px-6 py-4 whitespace-nowrap text-center font-bold font-mono text-slate-900">
                                <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                    {{ $user->display_employees_count ?? $user->employees_count }} emp
                                </span>
                            </td>
                            <!-- Role -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_superadmin)
                                    <span class="bg-black text-white text-[10px] font-black px-2.5 py-1 rounded-full border border-black inline-flex items-center shadow-sm">
                                        <i data-lucide="shield" class="w-3.5 h-3.5 mr-1 text-white"></i> Superadmin
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-slate-200 inline-flex items-center">
                                        <i data-lucide="building" class="w-3.5 h-3.5 mr-1 text-slate-400"></i> Tenant / Cliente
                                    </span>
                                @endif
                            </td>
                            <!-- Status Toggle -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($user->id !== Auth::id())
                                    <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center space-x-1 font-extrabold text-[10px] px-2.5 py-1 rounded-full border transition-all shadow-sm {{ $user->is_active ? 'bg-black text-white border-black hover:bg-slate-800' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}" title="Clic para cambiar estado">
                                            <i data-lucide="{{ $user->is_active ? 'check-circle' : 'power' }}" class="w-3 h-3"></i>
                                            <span>{{ $user->is_active ? 'Activo' : 'Suspendido' }}</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="bg-black text-white text-[10px] font-black px-2.5 py-1 rounded-full inline-flex items-center">
                                        <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Activo
                                    </span>
                                @endif
                            </td>
                            <!-- Created At -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Edit Button -->
                                    <button data-modal-target="userModal" data-modal-toggle="userModal" 
                                            onclick='openEditModal(@json($user))'
                                            class="inline-flex items-center justify-center p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg transition-colors border border-slate-300 shadow-sm"
                                            title="Editar Usuario / Cambiar Contraseña">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </button>

                                    <!-- Quick Assign Sede Button -->
                                    <button data-modal-target="assignSedeModal-{{ $user->id }}" data-modal-toggle="assignSedeModal-{{ $user->id }}" 
                                            class="inline-flex items-center justify-center p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg transition-colors border border-slate-300 shadow-sm"
                                            title="Asignar / Editar Sede">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                    </button>

                                    <!-- Delete Button -->
                                     @if($user->id !== Auth::id())
                                         <button data-modal-target="deleteUserModal-{{ $user->id }}" data-modal-toggle="deleteUserModal-{{ $user->id }}" class="inline-flex items-center justify-center p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors border border-red-200 shadow-sm" title="Eliminar Cuenta">
                                             <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                         </button>
                                     @else
                                         <button disabled class="inline-flex items-center justify-center p-1.5 bg-slate-50 text-slate-300 rounded-lg cursor-not-allowed border border-slate-200" title="No te puedes eliminar a ti mismo">
                                             <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                         </button>
                                     @endif
                                 </div>
                             </td>
                         </tr>

                         <!-- MODAL DE ADVERTENCIA Y CONFIRMACIÓN PARA ELIMINAR USUARIO -->
                         <div id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                             <div class="relative p-4 w-full max-w-md max-h-full">
                                 <div class="relative bg-white rounded-2xl shadow-2xl border border-red-200 overflow-hidden">
                                     <!-- Alert Banner Header -->
                                     <div class="p-6 text-center">
                                         <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-red-200 shadow-inner">
                                             <i data-lucide="alert-triangle" class="w-7 h-7 text-red-600"></i>
                                         </div>
                                         <h3 class="text-lg font-extrabold text-slate-900 mb-1">¿Eliminar a {{ $user->name }}?</h3>
                                         <p class="text-xs text-slate-500 font-mono font-semibold">{{ $user->email }}</p>

                                         <div class="mt-4 p-3 bg-red-50 rounded-xl border border-red-100 text-left space-y-2 text-xs text-red-800 font-medium">
                                             <p class="font-bold flex items-center text-red-900">
                                                 <i data-lucide="shield-alert" class="w-4 h-4 mr-1.5 text-red-600 flex-shrink-0"></i>
                                                 Esta acción es irreversible y eliminará:
                                             </p>
                                             <ul class="list-disc list-inside text-[11px] space-y-1 pl-1 text-red-700 font-semibold">
                                                 <li>Todos sus <strong>empleados y huellas</strong>.</li>
                                                 <li>Sus <strong>dispositivos biométricos / huelleros</strong>.</li>
                                                 <li>Todas sus <strong>marcaciones y reportes</strong>.</li>
                                                 <li>Su <strong>sede asignada y cuenta de acceso</strong>.</li>
                                             </ul>
                                         </div>
                                     </div>

                                     <!-- Footer Action Buttons -->
                                     <div class="flex items-center justify-end space-x-3 p-4 bg-slate-50 border-t border-slate-200">
                                         <button data-modal-hide="deleteUserModal-{{ $user->id }}" type="button" class="text-slate-700 bg-white hover:bg-slate-100 font-extrabold rounded-xl text-xs px-4 py-2.5 border border-slate-300 shadow-sm">
                                             Cancelar
                                         </button>
                                         <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                             @csrf
                                             @method('DELETE')
                                             <button type="submit" class="text-white bg-red-600 hover:bg-red-700 font-extrabold rounded-xl text-xs px-5 py-2.5 shadow-md flex items-center space-x-1.5">
                                                 <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                 <span>Sí, Eliminar Usuario y Datos</span>
                                             </button>
                                         </form>
                                     </div>
                                 </div>
                             </div>
                         </div>

                        <!-- MODAL RÁPIDO PARA ASIGNAR / UNIR A SEDE COMPARTIDA -->
                        <div id="assignSedeModal-{{ $user->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                            <div class="relative p-4 w-full max-w-md max-h-full">
                                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
                                    <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center">
                                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-slate-900"></i>
                                            Asignar Sede a {{ $user->name }}
                                        </h3>
                                        <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="assignSedeModal-{{ $user->id }}">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <form action="{{ route('users.assign-sede', $user) }}" method="POST" class="p-4 space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Seleccionar Sede Existente (Código Único)</label>
                                            <select name="sede_id" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold mb-2">
                                                <option value="">-- Selecciona una Sede Registrada --</option>
                                                @foreach($allSedes as $s)
                                                    <option value="{{ $s->id }}" {{ $user->sede_id == $s->id ? 'selected' : '' }}>
                                                        📍 [{{ $s->code }}] {{ $s->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="relative flex py-1 items-center">
                                            <div class="flex-grow border-t border-slate-200"></div>
                                            <span class="flex-shrink mx-2 text-[10px] text-slate-400 font-bold uppercase">O Crear Nueva Sede</span>
                                            <div class="flex-grow border-t border-slate-200"></div>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nombre de la Nueva Sede</label>
                                            <input type="text" name="new_sede_name" placeholder="Ej: Sede Bogotá, Sede Occidente..." class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                                        </div>

                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[10px] text-slate-600 font-semibold leading-relaxed">
                                            💡 <strong>Sedes Compartidas:</strong> Si asignas la misma sede a 2 o más usuarios, ambos usuarios compartirán los marcajes, huelleros y empleados de esa sede.
                                        </div>

                                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                                            <button data-modal-hide="assignSedeModal-{{ $user->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2 border border-slate-300">Cancelar</button>
                                            <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-4 py-2 shadow-md">Guardar Sede</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500 font-medium">
                                <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                No hay otros usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $users->links('partials.pagination') }}
            </div>
        @endif
    </div>

    <!-- SECCIÓN DE GESTIÓN DE SEDES Y UBICACIONES -->
    <div class="space-y-4 pt-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="space-y-1">
                <h3 class="font-heading font-extrabold text-sm text-slate-900 flex items-center">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-slate-900"></i>
                    Sedes y Ubicaciones de la Plataforma
                </h3>
                <p class="text-xs text-slate-500 font-semibold">Administra las sedes globales, edita nombres/códigos o elimina ubicaciones obsoletas</p>
            </div>
            <button data-modal-target="createSedeModal" data-modal-toggle="createSedeModal" class="btn-hover-grow flex items-center justify-center space-x-2 bg-slate-900 hover:bg-black text-white font-extrabold text-xs px-4 py-2.5 rounded-xl shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4 text-white"></i>
                <span>Crear Nueva Sede</span>
            </button>
        </div>

        <!-- Tabla de Sedes -->
        <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-800">
                    <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-4">Código Sede</th>
                            <th scope="col" class="px-6 py-4">Nombre de la Sede</th>
                            <th scope="col" class="px-6 py-4">Descripción</th>
                            <th scope="col" class="px-6 py-4 text-center">Empresas / Usuarios</th>
                            <th scope="col" class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($allSedes as $sedeItem)
                            <tr class="bg-white hover:bg-slate-50 transition-colors">
                                <!-- Código -->
                                <td class="px-6 py-4 font-mono text-xs whitespace-nowrap">
                                    <span class="bg-slate-100 text-slate-900 font-black text-xs px-3 py-1.5 rounded-xl border border-slate-200 inline-flex items-center">
                                        📍 {{ $sedeItem->code }}
                                    </span>
                                </td>
                                <!-- Nombre -->
                                <td class="px-6 py-4 whitespace-nowrap font-extrabold text-slate-900 text-sm">
                                    {{ $sedeItem->name }}
                                </td>
                                <!-- Descripción -->
                                <td class="px-6 py-4 text-xs font-medium text-slate-500 max-w-xs truncate">
                                    {{ $sedeItem->description ?? 'Sin descripción adicional' }}
                                </td>
                                <!-- Usuarios vinculados -->
                                <td class="px-6 py-4 text-center font-bold text-xs whitespace-nowrap">
                                    <span class="bg-slate-100 text-slate-800 px-3 py-1 rounded-xl border border-slate-200 font-mono font-extrabold">
                                        {{ $sedeItem->users()->count() }} usuario(s)
                                    </span>
                                </td>
                                <!-- Acciones -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Editar Sede -->
                                        <button data-modal-target="editSedeModal-{{ $sedeItem->id }}" data-modal-toggle="editSedeModal-{{ $sedeItem->id }}" class="btn-hover-grow inline-flex items-center justify-center gap-1.5 text-slate-700 bg-white hover:bg-slate-100 font-extrabold text-xs px-3 py-1.5 rounded-xl border border-slate-300 shadow-xs">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            <span>Editar</span>
                                        </button>

                                        <!-- Eliminar Sede -->
                                        <form action="{{ route('sedes.destroy', $sedeItem) }}" method="POST" onsubmit="return confirm('¿Eliminar la sede {{ $sedeItem->name }} [{{ $sedeItem->code }}]? Los usuarios vinculados quedarán sin sede.')" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-hover-grow inline-flex items-center justify-center gap-1.5 text-red-600 bg-white hover:bg-red-50 border border-red-200 font-extrabold text-xs px-3 py-1.5 rounded-xl shadow-xs">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                <span>Borrar</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- MODAL EDITAR SEDE -->
                            <div id="editSedeModal-{{ $sedeItem->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                                <div class="relative p-4 w-full max-w-md max-h-full">
                                    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
                                        <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                                            <h3 class="text-sm font-extrabold text-slate-900 flex items-center">
                                                <i data-lucide="edit-3" class="w-4 h-4 mr-2 text-slate-900"></i>
                                                Editar Sede: {{ $sedeItem->name }}
                                            </h3>
                                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editSedeModal-{{ $sedeItem->id }}">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        <form action="{{ route('sedes.update', $sedeItem) }}" method="POST" class="p-4 space-y-4">
                                            @csrf
                                            @method('PUT')
                                            
                                            <div>
                                                <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Código Único de Sede *</label>
                                                <input type="text" name="code" value="{{ $sedeItem->code }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nombre de la Sede *</label>
                                                <input type="text" name="name" value="{{ $sedeItem->name }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Descripción / Notas (Opcional)</label>
                                                <textarea name="description" rows="2" placeholder="Notas sobre la dirección, ciudad o referencias..." class="bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">{{ $sedeItem->description }}</textarea>
                                            </div>

                                            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                                                <button data-modal-hide="editSedeModal-{{ $sedeItem->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2 border border-slate-300">Cancelar</button>
                                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-4 py-2 shadow-md">Actualizar Sede</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 font-medium">
                                    <i data-lucide="map-pin-off" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                    No hay sedes registradas en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FLOWBITE USER MODAL (CREAR / EDITAR) -->
<div id="userModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 id="modalTitle" class="text-base font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="user-plus" class="w-5 h-5 mr-2 text-slate-900"></i>
                    Registrar Nuevo Usuario
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="userModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Form -->
            <form id="userForm" action="{{ route('users.store') }}" method="POST" class="p-4 md:p-5 space-y-4">
                @csrf
                <input type="hidden" id="methodField" name="_method" value="POST">

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nombre Completo / Empresa *</label>
                    <input type="text" name="name" id="userNameInput" required placeholder="Ej: Mi Empresa S.A.S." class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" id="userEmailInput" required placeholder="Ej: admin@empresa.com" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Sede / Ubicación (Opcional)</label>
                    <input type="text" name="sede" id="userSedeInput" placeholder="Ej: Sede Principal, NOC, Sede Auxiliar..." class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                </div>

                <div>
                    <label id="passwordLabel" class="block text-[10px] font-black uppercase text-slate-700 mb-1">Contraseña *</label>
                    <input type="password" name="password" id="userPasswordInput" placeholder="Mínimo 6 caracteres" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="userConfirmInput" placeholder="Repite la contraseña" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                </div>

                <div class="flex items-center p-3 bg-slate-50 border border-slate-200 rounded-xl space-x-3">
                    <input id="userActiveToggle" name="is_active" type="checkbox" value="1" class="w-4 h-4 text-black border-slate-300 rounded focus:ring-black focus:ring-offset-0 focus:ring-1" checked>
                    <label for="userActiveToggle">
                        <span class="block text-xs font-black text-slate-900">Cuenta Activa</span>
                        <span class="block text-[10px] text-slate-400 font-semibold">Permite el acceso al sistema. Si se desmarca, la cuenta quedará suspendida.</span>
                    </label>
                </div>

                <div class="flex items-center p-3 bg-slate-50 border border-slate-200 rounded-xl space-x-3">
                    <input id="userSuperadminToggle" name="is_superadmin" type="checkbox" value="1" class="w-4 h-4 text-black border-slate-300 rounded focus:ring-black focus:ring-offset-0 focus:ring-1">
                    <label for="userSuperadminToggle">
                        <span class="block text-xs font-black text-slate-900">Otorgar privilegios de Super Administrador</span>
                        <span class="block text-[10px] text-slate-400 font-semibold">Permite ver y gestionar todas las cuentas de la plataforma SaaS.</span>
                    </label>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                    <button data-modal-hide="userModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2.5 border border-slate-300">
                        Cancelar
                    </button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-5 py-2.5 shadow-md">
                        Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
<!-- MODAL CREAR NUEVA SEDE -->
<div id="createSedeModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
            <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-slate-900"></i>
                    Registrar Nueva Sede
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="createSedeModal">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form action="{{ route('sedes.store') }}" method="POST" class="p-4 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nombre de la Sede *</label>
                    <input type="text" name="name" required placeholder="Ej: Sede Bogotá Norte, Sede Principal, etc." class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Código Personalizado (Opcional)</label>
                    <input type="text" name="code" placeholder="Ej: SEDE-001 (Se autogenera si se deja en blanco)" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Descripción / Notas (Opcional)</label>
                    <textarea name="description" rows="2" placeholder="Ubicación física, dirección o notas de la sede..." class="bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                    <button data-modal-hide="createSedeModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-4 py-2 shadow-md">Guardar Sede</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('modalTitle').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5 mr-2 text-slate-900"></i> Registrar Nuevo Usuario / Empresa';
        document.getElementById('userForm').action = "{{ route('users.store') }}";
        document.getElementById('methodField').value = 'POST';
        
        // Limpiar inputs
        document.getElementById('userNameInput').value = '';
        document.getElementById('userEmailInput').value = '';
        document.getElementById('userSedeInput').value = '';
        document.getElementById('userPasswordInput').value = '';
        document.getElementById('userConfirmInput').value = '';
        document.getElementById('userPasswordInput').required = true;
        document.getElementById('passwordLabel').textContent = 'Contraseña *';
        document.getElementById('userActiveToggle').checked = true;
        document.getElementById('userActiveToggle').disabled = false;
        document.getElementById('userSuperadminToggle').checked = false;
        document.getElementById('userSuperadminToggle').disabled = false;

        if (window.lucide) lucide.createIcons();
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').innerHTML = '<i data-lucide="user-cog" class="w-5 h-5 mr-2 text-slate-900"></i> Editar Usuario: ' + user.name;
        document.getElementById('userForm').action = "/users/" + user.id;
        document.getElementById('methodField').value = 'PUT';
        
        // Rellenar inputs
        document.getElementById('userNameInput').value = user.name;
        document.getElementById('userEmailInput').value = user.email;
        document.getElementById('userSedeInput').value = user.sede || '';
        document.getElementById('userPasswordInput').value = '';
        document.getElementById('userConfirmInput').value = '';
        document.getElementById('userPasswordInput').required = false;
        document.getElementById('passwordLabel').textContent = 'Nueva Contraseña (dejar en blanco para no cambiar)';
        document.getElementById('userActiveToggle').checked = user.is_active;
        document.getElementById('userSuperadminToggle').checked = user.is_superadmin;

        // Evitar que el usuario activo se altere sus propios privilegios
        const currentUserId = {{ Auth::id() }};
        if (user.id === currentUserId) {
            document.getElementById('userSuperadminToggle').disabled = true;
            document.getElementById('userActiveToggle').disabled = true;
        } else {
            document.getElementById('userSuperadminToggle').disabled = false;
            document.getElementById('userActiveToggle').disabled = false;
        }

        if (window.lucide) lucide.createIcons();
    }
</script>
@endpush
@endsection
