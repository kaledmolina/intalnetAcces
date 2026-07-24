@extends('layouts.app')

@section('title', 'Huelleros Hikvision ISAPI - IntalnetAcces')
@section('page-header', 'Biométricos Hikvision ISAPI')
@section('page-sub-header', 'Configuración de red, credenciales y edición de huelleros en tiempo real')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="bw-card p-5 rounded-2xl flex items-center justify-between shadow-sm bg-white border border-slate-200">
        <div class="flex items-center space-x-3">
            <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                <i data-lucide="cpu" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-extrabold text-lg text-slate-900">Huelleros Registrados</h3>
                <p class="text-xs text-slate-500 font-medium">Terminales de control de acceso conectadas en red local</p>
            </div>
        </div>

        <button type="button" data-modal-target="deviceModal" data-modal-toggle="deviceModal" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 flex items-center space-x-2 shadow-md">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>+ Agregar Huellero</span>
        </button>
    </div>

    <!-- Terminal Status Grid (Black & White Style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($devices as $device)
            <div class="bw-card p-6 rounded-2xl space-y-5 relative overflow-hidden shadow-sm bg-white border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-xl {{ $device->status === 'online' ? 'bg-black text-white' : 'bg-slate-100 text-slate-400 border border-slate-300' }}">
                            <i data-lucide="cpu" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="font-heading font-extrabold text-lg text-slate-900 leading-tight">{{ $device->name }}</h3>
                            <span class="text-xs text-slate-600 font-bold">{{ $device->location }}</span>
                        </div>
                    </div>

                    @if($device->status === 'online')
                        <span class="bg-black text-white text-xs font-extrabold px-3 py-1 rounded-full border border-black inline-flex items-center shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-white mr-2 animate-ping"></span>
                            ONLINE
                        </span>
                    @else
                        <span class="bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1 rounded-full border border-slate-300 inline-flex items-center">
                            <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>
                            DESCONECTADO
                        </span>
                    @endif
                </div>

                <!-- Technical Specs Grid -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-500 block uppercase font-bold text-[10px] tracking-wider">Dirección IP</span>
                        <span class="font-mono text-slate-900 font-extrabold text-sm block mt-0.5">{{ $device->ip_address }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-500 block uppercase font-bold text-[10px] tracking-wider">Protocolo / Puerto</span>
                        <span class="font-mono text-slate-900 font-extrabold text-sm block mt-0.5">{{ strtoupper($device->protocol) }} : {{ $device->port }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-500 block uppercase font-bold text-[10px] tracking-wider">Usuario ISAPI</span>
                        <span class="font-mono text-slate-900 font-extrabold text-sm block mt-0.5">{{ $device->username }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-500 block uppercase font-bold text-[10px] tracking-wider">Última Sincronización</span>
                        <span class="text-slate-700 font-bold block mt-0.5">{{ $device->last_sync_at ? $device->last_sync_at->format('d/m/Y H:i') : 'Sin sincronizar' }}</span>
                    </div>
                </div>

                <!-- Botones de Acción Corporativos -->
                <div class="pt-2 flex flex-wrap items-center justify-between gap-2">
                    <form action="{{ route('devices.test', $device) }}" method="POST" class="flex-1 min-w-[130px]">
                        @csrf
                        <button type="submit" class="w-full text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 font-extrabold rounded-xl text-xs px-3 py-2 flex items-center justify-center space-x-1 shadow-sm">
                            <i data-lucide="radio" class="w-3.5 h-3.5 text-slate-900"></i>
                            <span>Probar Conexión</span>
                        </button>
                    </form>

                    <form action="{{ route('devices.sync', $device) }}" method="POST" class="flex-1 min-w-[130px]">
                        @csrf
                        <button type="submit" class="w-full text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-3 py-2 flex items-center justify-center space-x-1 shadow-md">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-white"></i>
                            <span>Sincronizar</span>
                        </button>
                    </form>

                    <form action="{{ route('devices.configure-auto', $device) }}" method="POST" class="flex-1 min-w-[150px]">
                        @csrf
                        <button type="submit" class="w-full text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 font-extrabold rounded-xl text-xs px-3 py-2 flex items-center justify-center space-x-1 shadow-sm" title="Activar modo de marcación directa sin presionar teclas">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-slate-900"></i>
                            <span>Auto Marcación (Sin Teclas)</span>
                        </button>
                    </form>

                    <!-- Botón Editar Huellero -->
                    <button type="button" data-modal-target="editDeviceModal_{{ $device->id }}" data-modal-toggle="editDeviceModal_{{ $device->id }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-xl text-xs px-3 py-2 flex items-center justify-center space-x-1 shadow-sm">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-900"></i>
                        <span>Editar</span>
                    </button>

                    <!-- Botón Eliminar Huellero -->
                    <form action="{{ route('devices.destroy', $device) }}" method="POST" onsubmit="return confirm('¿Eliminar el huellero {{ $device->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-xl text-xs px-3 py-2 flex items-center justify-center space-x-1 shadow-sm">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-slate-900"></i>
                            <span>Borrar</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- FLOWBITE MODAL EDITAR HUELLERO -->
            <div id="editDeviceModal_{{ $device->id }}" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
                <div class="relative p-4 w-full max-w-lg max-h-full">
                    <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
                        <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                            <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                                <i data-lucide="edit-3" class="w-5 h-5 mr-2 text-slate-900"></i>
                                Editar Huellero: {{ $device->name }}
                            </h3>
                            <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editDeviceModal_{{ $device->id }}">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <form action="{{ route('devices.update', $device) }}" method="POST" class="p-4 md:p-5 space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Dispositivo *</label>
                                <input type="text" name="name" value="{{ old('name', $device->name) }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-semibold">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Dirección IP *</label>
                                    <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Puerto *</label>
                                    <input type="number" name="port" value="{{ old('port', $device->port) }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Usuario ISAPI *</label>
                                    <input type="text" name="username" value="{{ old('username', $device->username) }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-semibold">
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Contraseña *</label>
                                    <input type="password" name="password" value="{{ old('password', $device->password) }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Protocolo</label>
                                    <select name="protocol" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-semibold">
                                        <option value="https" {{ $device->protocol === 'https' ? 'selected' : '' }}>HTTPS (Recomendado)</option>
                                        <option value="http" {{ $device->protocol === 'http' ? 'selected' : '' }}>HTTP</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Ubicación</label>
                                    <input type="text" name="location" value="{{ old('location', $device->location) }}" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                <button data-modal-hide="editDeviceModal_{{ $device->id }}" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                                <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- FLOWBITE MODAL NUEVO HUELLERO EN BLANCO Y NEGRO -->
<div id="deviceModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-300">
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="plus-circle" class="w-5 h-5 mr-2 text-slate-900"></i>
                    Agregar Huellero Hikvision
                </h3>
                <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="deviceModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="{{ route('devices.store') }}" method="POST" class="p-4 md:p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nombre del Dispositivo *</label>
                    <input type="text" name="name" required placeholder="Ej: Huellero Recepción" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Dirección IP *</label>
                        <input type="text" name="ip_address" required placeholder="Ej: 192.168.1.10" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Puerto *</label>
                        <input type="number" name="port" required value="443" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Usuario ISAPI *</label>
                        <input type="text" name="username" required value="admin" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Contraseña *</label>
                        <input type="password" name="password" required value="Colombia2026**" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-mono font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Protocolo</label>
                        <select name="protocol" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-semibold">
                            <option value="https" selected>HTTPS (Recomendado)</option>
                            <option value="http">HTTP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Ubicación</label>
                        <input type="text" name="location" placeholder="Ej: Entrada Principal" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                    <button data-modal-hide="deviceModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-sm px-5 py-2.5 border border-slate-300">Cancelar</button>
                    <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-sm px-5 py-2.5 shadow-md">Guardar Huellero</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
