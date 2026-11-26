{{-- Conexión OAuth 2.1: Claude y ChatGPT piden usuario y contraseña de IntalnetAcces, sin copiar tokens. --}}
<div class="bg-white rounded-2xl border-2 border-black shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-heading font-extrabold text-sm text-slate-900 flex items-center space-x-2">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Conectar con inicio de sesión (recomendado)</span>
            </h3>
            <p class="text-xs text-slate-500 font-semibold mt-1 max-w-2xl">
                Claude y ChatGPT pueden entrar con tu usuario y contraseña de IntalnetAcces: pega la URL del
                conector, pulsa «Conectar» e inicia sesión. No hace falta generar ni copiar tokens.
            </p>
        </div>
        <span class="bg-black text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase">OAuth 2.1</span>
    </div>

    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <p class="text-[11px] font-extrabold text-slate-900 flex items-center space-x-1.5">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Claude</span>
            </p>
            <ol class="mt-2 space-y-1.5 text-[11px] text-slate-600 font-semibold list-decimal list-inside leading-relaxed">
                <li>Ajustes → Conectores → Añadir conector personalizado.</li>
                <li>Pega la URL del conector y ponle un nombre.</li>
                <li>En autenticación elige «Iniciar sesión ahora».</li>
                <li>En cliente OAuth deja «Usar la identidad publicada de Claude» (CIMD); si falla, «Registrar automáticamente».</li>
                <li>Inicia sesión y pulsa «Autorizar acceso».</li>
            </ol>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <p class="text-[11px] font-extrabold text-slate-900 flex items-center space-x-1.5">
                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                <span>ChatGPT</span>
            </p>
            <ol class="mt-2 space-y-1.5 text-[11px] text-slate-600 font-semibold list-decimal list-inside leading-relaxed">
                <li>Configuración → Conectores → Avanzado → Crear conector.</li>
                <li>Pega la URL del conector.</li>
                <li>Autenticación: OAuth (CIMD o registro automático, sin client ID ni secret).</li>
                <li>Conectar → iniciar sesión → autorizar.</li>
            </ol>
        </div>

        <div class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-start space-x-2">
            <i data-lucide="shield-check" class="w-4 h-4 flex-shrink-0 text-slate-700"></i>
            <p class="text-[11px] text-slate-600 font-medium leading-relaxed">
                Tu contraseña se escribe siempre en la web de IntalnetAcces, nunca dentro de Claude o ChatGPT.
                El acceso queda limitado a tu sede y aparece como «(OAuth)» en «Mis tokens», donde puedes
                revocarlo cuando quieras.
            </p>
        </div>

        <p class="md:col-span-2 text-[11px] text-slate-500 font-semibold leading-relaxed">
            Cursor, VS Code y Freebuff siguen usando la cabecera
            <span class="font-mono">Authorization: Bearer</span> con un token de los de abajo.
        </p>
    </div>
</div>
