@extends('layouts.app')

@section('title', 'Conectores IA (MCP) - IntalnetAcces')
@section('page-header', 'Conectores IA (MCP)')
@section('page-sub-header', 'Servidor de solo lectura para Claude, ChatGPT, Freebuff y otros clientes MCP')

@section('content')
<div class="space-y-6">

    @if(session('mcp_plain_text_token'))
        <div class="rounded-2xl border-2 border-black bg-black text-white p-5 shadow-lg space-y-3" id="mcpTokenCard">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-heading font-extrabold text-sm flex items-center space-x-2">
                        <i data-lucide="key-round" class="w-4 h-4"></i>
                        <span>Token de {{ session('mcp_token_user') }} — se muestra una sola vez</span>
                    </h3>
                    <p class="text-[11px] text-slate-300 font-semibold mt-1">Cópialo ahora. Al salir de esta página no se podrá volver a ver.</p>
                </div>
                <button type="button" onclick="copyMcpToken()" class="flex items-center space-x-1 bg-white text-black font-extrabold text-[11px] px-3 py-2 rounded-lg hover:bg-slate-200 transition-colors">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span id="mcpCopyLabel">Copiar</span>
                </button>
            </div>
            <code id="mcpTokenValue" class="block w-full break-all bg-slate-900 border border-slate-700 rounded-xl p-3 text-[11px] font-mono text-emerald-300">{{ session('mcp_plain_text_token') }}</code>
            <p class="text-[11px] text-slate-300 font-semibold">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5 inline mr-1"></i>
                Este token hereda la sede y la empresa del usuario seleccionado. No lo comparta entre empresas.
            </p>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700 text-xs font-bold space-y-1">
            @foreach($errors->all() as $error)
                <p class="flex items-center space-x-2"><i data-lucide="alert-circle" class="w-4 h-4"></i><span>{{ $error }}</span></p>
            @endforeach
        </div>
    @endif

    @include('mcp.partials.oauth')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Datos de conexión -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-heading font-extrabold text-sm text-slate-900">Datos del servidor MCP</h3>
                        <p class="text-xs text-slate-500 font-semibold">Transporte HTTP (Streamable HTTP) con token Bearer</p>
                    </div>
                    <span class="bg-black text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase">Solo lectura</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider">URL del conector</p>
                        <p class="font-mono text-xs font-bold text-slate-900 break-all mt-1">{{ $endpoint }}</p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider">Autenticación</p>
                        <p class="font-mono text-xs font-bold text-slate-900 mt-1">OAuth 2.1 o Authorization: Bearer &lt;token&gt;</p>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider mb-2">Herramientas disponibles</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(['tardanzas_del_dia', 'entradas_del_dia', 'salidas_del_dia', 'tardanzas_por_rango', 'resumen_asistencia_por_sede', 'marcaciones_de_empleado', 'buscar_empleado', 'horario_de_empleado', 'ranking_tardanzas', 'ausencias_del_periodo', 'marcaciones_incompletas', 'horas_trabajadas', 'detalle_de_empleado', 'catalogo_sedes_departamentos', 'incidencias_del_periodo', 'exportar_reporte'] as $tool)
                            <span class="bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-mono font-bold px-2 py-1 rounded-md">{{ $tool }}</span>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-slate-500 font-semibold mt-2">Máximo 200 filas por respuesta (1000 en exportar_reporte) y 60 peticiones por minuto por token.</p>

                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider mb-2 mt-4">Prompts</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(['informe_semanal', 'resumen_del_dia'] as $prompt)
                            <span class="bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-mono font-bold px-2 py-1 rounded-md">{{ $prompt }}</span>
                        @endforeach
                    </div>

                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider mb-2 mt-4">Recursos</p>
                    <span class="bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-mono font-bold px-2 py-1 rounded-md">intalnet://docs/diccionario-de-campos</span>
                    <p class="text-[11px] text-slate-500 font-semibold mt-2">exportar_reporte acepta además los tipos <span class="font-mono">marcaciones_incompletas</span>, <span class="font-mono">novedades</span> y <span class="font-mono">sin_asignar</span>.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <h3 class="font-heading font-extrabold text-sm text-slate-900">Configuración para Freebuff / Cursor / VS Code</h3>
                <pre class="bg-slate-950 text-emerald-300 rounded-xl p-4 text-[11px] font-mono overflow-x-auto leading-relaxed">{
  "mcpServers": {
    "intalnet-attendance": {
      "type": "http",
      "url": "{{ $endpoint }}",
      "headers": {
        "Authorization": "Bearer &lt;token-generado&gt;"
      }
    }
  }
}</pre>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[11px] font-extrabold text-slate-800 flex items-center space-x-2">
                        <i data-lucide="bot" class="w-4 h-4"></i>
                        <span>ChatGPT</span>
                    </p>
                    <p class="text-[11px] text-slate-600 font-medium mt-1 leading-relaxed">
                        Usa la conexión con inicio de sesión de arriba: pega la URL y elige «OAuth». ChatGPT se registra solo y te pedirá iniciar sesión; no uses «sin autenticación», porque expondría los datos.
                    </p>
                </div>
            </div>
        </div>

        <!-- Generar token -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-heading font-extrabold text-sm text-slate-900">Generar token</h3>
                <p class="text-xs text-slate-500 font-semibold mt-1">El token pertenece al usuario y hereda su empresa y sede.</p>

                <form action="{{ route('mcp.tokens.store') }}" method="POST" class="space-y-3 mt-4">
                    @csrf
                    <div>
                        <label for="mcp_user_id" class="block text-[11px] font-extrabold text-slate-700 mb-1">Usuario</label>
                        <select name="user_id" id="mcp_user_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl p-2.5 focus:ring-black focus:border-black outline-none">
                            <option value="">Seleccione un usuario...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }} — {{ $user->email }}{{ $user->is_active ? '' : ' (inactivo)' }}{{ $user->sedeRelation ? ' ['.$user->sedeRelation->code.']' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="mcp_token_name" class="block text-[11px] font-extrabold text-slate-700 mb-1">Nombre del token (opcional)</label>
                        <input type="text" name="name" id="mcp_token_name" maxlength="60" value="{{ old('name') }}" placeholder="freebuff, cursor, chatgpt..." class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl p-2.5 focus:ring-black focus:border-black outline-none">
                    </div>
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 bg-black hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl shadow-md transition-colors">
                        <i data-lucide="key-round" class="w-4 h-4"></i>
                        <span>Generar token</span>
                    </button>
                </form>

                <p class="text-[10px] text-slate-500 font-semibold mt-3 leading-relaxed">
                    El token se muestra una sola vez. Si se pierde, revóquelo y genere uno nuevo.
                </p>
            </div>

            <div class="bg-black text-white rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] uppercase font-black tracking-wider text-slate-400">Seguridad</p>
                <p class="text-xs font-semibold mt-2 leading-relaxed text-slate-200">
                    El tenant se deriva siempre del usuario del token. Ninguna herramienta acepta un identificador de empresa o sede como parámetro, y si se pide una sede ajena devuelve una lista vacía.
                </p>
            </div>
        </div>
    </div>

    @include('mcp.partials.guia')

    <!-- Tokens activos -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="font-heading font-extrabold text-sm text-slate-900">Tokens MCP activos</h3>
            <p class="text-xs text-slate-500 font-semibold">Solo aparecen tokens con permiso de lectura MCP.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Usuario</th>
                        <th class="px-6 py-3.5">Token</th>
                        <th class="px-6 py-3.5">Sede</th>
                        <th class="px-6 py-3.5">Creado</th>
                        <th class="px-6 py-3.5">Último uso</th>
                        <th class="px-6 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($tokens as $token)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-extrabold text-slate-900">{{ $token->tokenable?->name ?? 'Usuario eliminado' }}</p>
                                <p class="text-[11px] text-slate-500 font-semibold">{{ $token->tokenable?->email }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-slate-100 border border-slate-200 text-slate-800 text-[10px] font-mono font-bold px-2 py-1 rounded-md">{{ $token->name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($token->tokenable?->sedeRelation)
                                    <span class="bg-black text-white text-[10px] font-extrabold px-2.5 py-1 rounded-lg">{{ $token->tokenable->sedeRelation->code }}</span>
                                @else
                                    <span class="text-[11px] text-slate-500 font-semibold">{{ $token->tokenable?->sede ?? 'Sin sede' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">{{ $token->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">
                                {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'Nunca' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <form action="{{ route('mcp.tokens.destroy', $token) }}" method="POST" onsubmit="return confirm('¿Revocar el token {{ $token->name }}? El cliente dejará de tener acceso inmediatamente.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center space-x-1 bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-extrabold px-2.5 py-1.5 rounded-lg border border-red-200 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        <span>Revocar</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-xs text-slate-500 font-semibold">
                                No hay tokens MCP generados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(session('mcp_plain_text_token'))
    <script>
        function copyMcpToken() {
            const value = document.getElementById('mcpTokenValue').innerText;
            navigator.clipboard.writeText(value).then(() => {
                const label = document.getElementById('mcpCopyLabel');
                label.innerText = 'Copiado';
                setTimeout(() => { label.innerText = 'Copiar'; }, 2000);
            });
        }
    </script>
@endif
@endsection
