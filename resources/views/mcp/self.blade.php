@extends('layouts.app')

@section('title', 'Mi conector IA - IntalnetAcces')
@section('page-header', 'Mi conector IA')
@section('page-sub-header', 'Conecta tu asistente de IA a tus datos de asistencia (solo lectura)')

@section('content')
<div class="space-y-6">

    @if(session('mcp_plain_text_token'))
        <div class="rounded-2xl border-2 border-black bg-black text-white p-5 shadow-lg space-y-3">
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

    @unless($user->sede_id || $user->sede)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-xs font-bold flex items-start space-x-2">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
            <p>Tu cuenta no tiene una sede asignada. El token se puede generar, pero las herramientas devolverán listas vacías hasta que un administrador te asigne una sede.</p>
        </div>
    @endunless

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-heading font-extrabold text-sm text-slate-900">Datos de conexión</h3>
                        <p class="text-xs text-slate-500 font-semibold">Tu token solo puede leer los datos de tu empresa y sede</p>
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
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 sm:col-span-2">
                        <p class="text-[10px] uppercase font-black text-slate-400 tracking-wider">Sede asociada al token</p>
                        <p class="text-xs font-bold text-slate-900 mt-1">
                            @if($user->sedeRelation)
                                {{ $user->sedeRelation->name }} <span class="font-mono text-[11px] text-slate-500">[{{ $user->sedeRelation->code }}]</span>
                            @elseif($user->sede)
                                {{ $user->sede }}
                            @else
                                <span class="text-amber-700">Sin sede asignada</span>
                            @endif
                        </p>
                    </div>
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
        "Authorization": "Bearer &lt;tu-token&gt;"
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
                        Usa la conexión con inicio de sesión de arriba: pega la URL y elige «OAuth». ChatGPT se registra solo y te pedirá iniciar sesión; no uses «sin autenticación», porque dejaría los datos abiertos.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-heading font-extrabold text-sm text-slate-900">Generar mi token</h3>
                <p class="text-xs text-slate-500 font-semibold mt-1">Se crea siempre a tu nombre, con la sede de tu cuenta.</p>

                <form action="{{ route('mcp.self.tokens.store') }}" method="POST" class="space-y-3 mt-4">
                    @csrf
                    <div>
                        <label for="mcp_token_name" class="block text-[11px] font-extrabold text-slate-700 mb-1">Nombre del token (opcional)</label>
                        <input type="text" name="name" id="mcp_token_name" maxlength="60" value="{{ old('name') }}" placeholder="freebuff, cursor, mi-laptop..." class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl p-2.5 focus:ring-black focus:border-black outline-none">
                    </div>
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 bg-black hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl shadow-md transition-colors">
                        <i data-lucide="key-round" class="w-4 h-4"></i>
                        <span>Generar token</span>
                    </button>
                </form>

                <p class="text-[10px] text-slate-500 font-semibold mt-3 leading-relaxed">
                    Se muestra una sola vez. Si lo pierdes, revócalo y genera otro.
                </p>
            </div>

            <div class="bg-black text-white rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] uppercase font-black tracking-wider text-slate-400">Privacidad</p>
                <p class="text-xs font-semibold mt-2 leading-relaxed text-slate-200">
                    El servidor deduce tu empresa y sede del token. No puede consultar datos de otra sede ni modificar marcaciones, horarios o empleados.
                </p>
            </div>
        </div>
    </div>

    @include('mcp.partials.guia')

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="font-heading font-extrabold text-sm text-slate-900">Mis tokens</h3>
            <p class="text-xs text-slate-500 font-semibold">Solo se muestran los tokens de tu cuenta.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Token</th>
                        <th class="px-6 py-3.5">Creado</th>
                        <th class="px-6 py-3.5">Último uso</th>
                        <th class="px-6 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($tokens as $token)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-slate-100 border border-slate-200 text-slate-800 text-[10px] font-mono font-bold px-2 py-1 rounded-md">{{ $token->name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">{{ $token->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">
                                {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'Nunca' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <form action="{{ route('mcp.self.tokens.destroy', $token) }}" method="POST" onsubmit="return confirm('¿Revocar este token? El cliente dejará de tener acceso inmediatamente.');">
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
                            <td colspan="4" class="px-6 py-10 text-center text-xs text-slate-500 font-semibold">
                                Todavía no has generado ningún token.
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
