<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-100 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IntalnetAcces - Control de Asistencia Corporativo')</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        mono: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Vite React Refresh & Assets -->
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
    @livewireStyles
    <!-- Lucide Icons (Self-Hosted via Vite bundle) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        });
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
            color: #0f172a; 
            scroll-behavior: smooth;
        }
        h1, h2, h3, h4, .font-heading { 
            font-family: 'Outfit', sans-serif; 
        }
        
        /* Premium Card Styles */
        .bw-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .bw-card-hover {
            cursor: pointer;
        }
        .bw-card-hover:hover {
            border-color: #0f172a;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.06), 0 8px 15px -10px rgba(0, 0, 0, 0.04);
        }

        /* KPI Active Highlighting */
        .kpi-card {
            position: relative;
            overflow: hidden;
        }
        .kpi-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: transparent;
            transition: background-color 0.2s ease;
        }
        .kpi-card.active-kpi {
            border-color: #000000 !important;
            box-shadow: 0 8px 20px -8px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        .kpi-card.active-kpi::after {
            background-color: #000000;
        }

        /* Lateral Menu Transitions */
        #sidebar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
        }

        .sidebar-text {
            transition: opacity 0.25s ease-in-out, max-width 0.25s ease-in-out;
            max-width: 200px;
            opacity: 1;
            display: inline-block;
            white-space: nowrap;
        }

        #sidebar .nav-link {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        /* Sidebar link indicator */
        #sidebar .nav-link.bg-black::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 25%;
            height: 50%;
            width: 4px;
            background-color: #000000;
            border-radius: 9999px;
        }

        #sidebar .nav-link:not(.bg-black):hover {
            background-color: #f8fafc;
            color: #000000 !important;
            transform: translateX(4px);
        }
        #sidebar .nav-link:not(.bg-black):hover i {
            color: #000000 !important;
            transform: scale(1.08);
            transition: transform 0.2s ease;
        }

        /* Pulsing indicator */
        .pulse-online {
            animation: pulse-glow 2s infinite ease-in-out;
        }
        @keyframes pulse-glow {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }

        /* Premium Buttons Hover Styles */
        .btn-hover-grow {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-hover-grow:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .btn-hover-grow:active {
            transform: scale(0.98);
        }

        /* Micro-animations for general icons on hover */
        .icon-spin-hover:hover i[data-lucide="refresh-cw"],
        .icon-spin-hover:hover i[data-lucide="rotate-cw"] {
            transform: rotate(180deg);
            transition: transform 0.4s ease;
        }

        .icon-bounce-hover:hover i {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }

        /* Smooth fade-in for view content */
        .animate-fade-in {
            animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom scrollbar style */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (min-width: 1024px) {
            #sidebar.collapsed {
                width: 5.5rem !important; /* w-22 */
            }
            #sidebar.collapsed .sidebar-text {
                max-width: 0;
                opacity: 0;
                pointer-events: none;
                margin-left: 0 !important;
                overflow: hidden;
            }
            #sidebar.collapsed .sidebar-card {
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
                border: 0;
                overflow: hidden;
                pointer-events: none;
                transition: all 0.2s ease-in-out;
            }
            #sidebar.collapsed #syncBtnSidebar span {
                display: none;
            }
            #sidebar.collapsed #syncBtnSidebar {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            #sidebar.collapsed #toggleSidebarIcon {
                transform: rotate(180deg);
            }
            #sidebar.collapsed .nav-link {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            #sidebar.collapsed .nav-link i {
                margin-right: 0 !important;
            }
            #sidebar.collapsed .nav-link.bg-black::before {
                left: 2px;
            }
        }
    </style>
</head>
<body class="h-full antialiased bg-slate-50 text-slate-900">

    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Flowbite Monochrome Sidebar -->
        <aside id="sidebar" class="w-full lg:w-72 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col lg:sticky lg:top-0 lg:h-screen z-30">
            <!-- Brand Header -->
            <div class="h-20 px-6 border-b border-slate-200 flex items-center justify-between overflow-hidden flex-shrink-0">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-3 flex-shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-black flex items-center justify-center text-white shadow-md flex-shrink-0">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div class="sidebar-text leading-tight">
                        <span class="font-heading font-extrabold text-base text-slate-900 tracking-tight block">IntalnetAcces</span>
                    </div>
                </a>
                
                <!-- Desktop Collapse Button -->
                <button id="toggleSidebarBtn" class="hidden lg:flex items-center justify-center p-1.5 hover:bg-slate-100 rounded-lg text-slate-500 hover:text-slate-800 transition-all border border-slate-200 ml-2">
                    <i data-lucide="chevron-left" class="w-4 h-4 transition-transform duration-300" id="toggleSidebarIcon"></i>
                </button>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg transition-colors hover:bg-slate-100">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Sidebar Collapsible Content -->
            <div id="sidebarContent" class="hidden lg:flex flex-col flex-1 lg:overflow-y-auto">
                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5 text-sm font-semibold">
                    <a href="{{ route('dashboard') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Dashboard">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>

                    <a href="{{ route('employees.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('employees.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Empleados">
                        <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Empleados</span>
                    </a>

                    <a href="{{ route('departments.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('departments.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Departamentos">
                        <i data-lucide="building-2" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Departamentos</span>
                    </a>

                    <a href="{{ route('attendance.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('attendance.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Marcaciones">
                        <i data-lucide="clipboard-check" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Marcaciones</span>
                    </a>

                    <a href="{{ route('schedules.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('schedules.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Horarios">
                        <i data-lucide="clock" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Horarios</span>
                    </a>

                    <a href="{{ route('devices.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('devices.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Huelleros ISAPI">
                        <i data-lucide="cpu" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="sidebar-text">Huelleros ISAPI</span>
                    </a>

                    @if(Auth::user()->is_superadmin)
                        <div class="pt-4 border-t border-slate-200 mt-4 space-y-1.5">
                            <span class="px-4 text-[10px] font-black uppercase text-slate-400 tracking-wider">Administración</span>
                            
                            <a href="{{ route('users.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('users.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Usuarios">
                                <i data-lucide="user-cog" class="w-5 h-5 flex-shrink-0"></i>
                                <span class="sidebar-text">Usuarios</span>
                            </a>

                            <a href="{{ route('backups.index') }}" wire:navigate class="nav-link flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('backups.*') ? 'bg-black text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}" title="Respaldos">
                                <i data-lucide="database" class="w-5 h-5 flex-shrink-0"></i>
                                <span class="sidebar-text">Respaldos</span>
                            </a>
                        </div>
                    @endif

                 </nav>

                @php
                    $sidebarDevices = \App\Models\Device::all();
                @endphp
                <!-- Sidebar Footer Status Card -->
                <div class="mt-auto p-4 border-t border-slate-200 flex-shrink-0">
                    <div class="sidebar-card bg-slate-100 p-4 rounded-2xl border border-slate-200 space-y-3 transition-all duration-300 mb-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Terminales En Red</span>
                            <span class="w-2.5 h-2.5 rounded-full {{ $sidebarDevices->count() > 0 ? 'bg-black animate-pulse' : 'bg-slate-300' }}"></span>
                        </div>

                        <div class="text-xs text-slate-600 space-y-1 font-mono">
                            @forelse($sidebarDevices as $device)
                                <p class="flex items-center justify-between">
                                    <span class="text-slate-500 font-sans font-medium text-[11px] truncate max-w-[110px]" title="{{ $device->name }}">{{ $device->name }}:</span>
                                    <span class="font-bold text-slate-900 text-[11px]">{{ $device->ip_address }}</span>
                                </p>
                            @empty
                                <div class="text-center py-2 text-slate-400 font-sans">
                                    <i data-lucide="cpu" class="w-5 h-5 mx-auto mb-1 text-slate-300"></i>
                                    <p class="text-[11px] font-semibold italic">Sin huelleros configurados</p>
                                </div>
                            @endforelse
                        </div>

                        @if($sidebarDevices->count() > 0)
                            <!-- Última Sincronización -->
                            <div class="pt-2.5 border-t border-slate-200 text-center">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Sincronización</span>
                                <span id="lastSyncTimeText" class="text-[10px] font-bold text-slate-700 font-mono block mt-0.5 select-none">
                                    {{ $sidebarDevices->max('last_sync_at') ? $sidebarDevices->max('last_sync_at')->format('d/m/Y H:i') : 'Nunca' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($sidebarDevices->count() > 0)
                        <form action="{{ route('dashboard.sync') }}" method="POST" id="syncSidebarForm">
                            @csrf
                            <button type="submit" id="syncBtnSidebar" class="w-full py-2.5 px-3 bg-black hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center space-x-2 shadow-sm">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="syncIconSidebar"></i>
                                <span>Sincronizar ISAPI</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('devices.index') }}" wire:navigate class="w-full py-2.5 px-3 bg-black hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all flex items-center justify-center space-x-2 shadow-sm">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>+ Configurar Huellero</span>
                        </a>
                    @endif
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50">
            <!-- Flowbite Monochrome Header -->
            <header class="h-20 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">@yield('page-header', 'Dashboard')</h1>
                    <p class="text-xs text-slate-500 font-medium">@yield('page-sub-header', 'Plataforma Corporativa en Blanco y Negro')</p>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Live Clock Badge -->
                    <div class="hidden sm:flex items-center space-x-2 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-900">
                        <i data-lucide="clock" class="w-4 h-4 text-slate-700"></i>
                        <span id="liveClock">--:--:--</span>
                    </div>

                    <!-- User Profile Avatar -->
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-black font-extrabold text-white text-xs flex items-center justify-center border border-slate-800 shadow-sm flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden md:block">
                            <span class="text-xs font-bold text-slate-900 block leading-none">{{ Auth::user()->name }}</span>
                            <span class="text-[10px] text-slate-500 block mt-0.5 leading-none">{{ Auth::user()->email }}</span>
                        </div>
                        <!-- Edit Profile / Company Button -->
                        <button type="button" data-modal-target="profileModal" data-modal-toggle="profileModal" class="btn-hover-grow p-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 rounded-xl transition-colors shadow-sm" title="Editar Nombre de Empresa / Perfil">
                            <i data-lucide="building-2" class="w-4 h-4 text-slate-700"></i>
                        </button>

                        <!-- Logout Button -->
                        <form action="{{ route('logout') }}" method="POST" class="inline flex-shrink-0">
                            @csrf
                            <button type="submit" class="btn-hover-grow p-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 rounded-xl transition-colors shadow-sm ml-1" title="Cerrar Sesión">
                                <i data-lucide="log-out" class="w-4 h-4 text-slate-700"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Toast Notifications Container -->
            <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-3 max-w-sm w-full pointer-events-none">
                @if(session('success'))
                    <div class="toast-item pointer-events-auto bg-white border-2 border-black text-slate-900 rounded-2xl p-4 shadow-2xl flex items-start space-x-3 transform transition-all duration-300 translate-x-12 opacity-0" role="alert">
                        <div class="p-1.5 bg-black text-white rounded-lg flex-shrink-0 flex items-center justify-center">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-heading font-extrabold text-xs uppercase tracking-wider text-slate-900">Operación Exitosa</p>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium leading-relaxed">{{ session('success') }}</p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="closeToast(this)">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="toast-item pointer-events-auto bg-white border-2 border-red-500 text-slate-900 rounded-2xl p-4 shadow-2xl flex items-start space-x-3 transform transition-all duration-300 translate-x-12 opacity-0" role="alert">
                        <div class="p-1.5 bg-red-50 text-red-600 rounded-lg flex-shrink-0 flex items-center justify-center border border-red-200">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-heading font-extrabold text-xs uppercase tracking-wider text-red-600">Error</p>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium leading-relaxed">{{ session('error') }}</p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="closeToast(this)">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Main Page View Content -->
            <main class="flex-1 p-6 md:p-8 overflow-y-auto">
                <div class="animate-fade-in">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script>
        function closeToast(button) {
            const toast = button.closest('.toast-item');
            if (toast) {
                toast.classList.add('translate-x-12', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const isSuccess = type === 'success';
            const borderColorClass = isSuccess ? 'border-black' : 'border-red-500';
            const title = isSuccess ? 'Éxito' : 'Error';
            const icon = isSuccess ? 'check-circle' : 'alert-triangle';
            const iconBg = isSuccess ? 'bg-slate-100 text-slate-900' : 'bg-red-50 text-red-500';

            const toast = document.createElement('div');
            toast.className = `toast-item pointer-events-auto bg-white border-2 ${borderColorClass} text-slate-900 rounded-2xl p-4 shadow-2xl flex items-start space-x-3 transform transition-all duration-300 translate-x-12 opacity-0`;
            toast.setAttribute('role', 'alert');

            toast.innerHTML = `
                <div class="p-1 ${iconBg} rounded-lg border border-slate-300">
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-black uppercase tracking-wider">${title}</p>
                    <p class="text-[11px] text-slate-500 font-bold mt-0.5">${message}</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="closeToast(this)">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;

            container.appendChild(toast);

            if (window.lucide) {
                lucide.createIcons();
            }

            setTimeout(() => {
                toast.classList.remove('translate-x-12', 'opacity-0');
            }, 100);

            setTimeout(() => {
                toast.classList.add('translate-x-12', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        document.addEventListener('livewire:navigated', () => {
            // Re-inicializar Flowbite para activar modales, togglers y dropdowns en la nueva vista cargada por SPA
            if (typeof initFlowbite === 'function') {
                initFlowbite();
            }

            if (window.lucide) {
                lucide.createIcons();
            }

            // Registrar observador de mutaciones para asegurar que cualquier elemento dinámico nuevo (de Livewire o React) renderice sus iconos
            if (!window.lucideObserver) {
                window.lucideObserver = new MutationObserver((mutations) => {
                    let shouldCreate = false;
                    for (const mutation of mutations) {
                        if (mutation.addedNodes.length) {
                            for (const node of mutation.addedNodes) {
                                if (node.nodeType === 1) {
                                    if (node.querySelector('[data-lucide]') || node.hasAttribute('data-lucide')) {
                                        shouldCreate = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if (shouldCreate) break;
                    }
                    if (shouldCreate && window.lucide) {
                        window.lucideObserver.disconnect();
                        lucide.createIcons();
                        window.lucideObserver.observe(document.body, { childList: true, subtree: true });
                    }
                });
                window.lucideObserver.observe(document.body, { childList: true, subtree: true });
            }

            // Toast Animation Logic
            const toasts = document.querySelectorAll('.toast-item');
            toasts.forEach((toast, index) => {
                setTimeout(() => {
                    toast.classList.remove('translate-x-12', 'opacity-0');
                }, 100 * index + 100);

                setTimeout(() => {
                    toast.classList.add('translate-x-12', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            });

            // Live Clock
            function updateClock() {
                const now = new Date();
                const clockEl = document.getElementById('liveClock');
                if (clockEl) {
                    clockEl.textContent = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
            }
            updateClock();
            if (window.clockInterval) clearInterval(window.clockInterval);
            window.clockInterval = setInterval(updateClock, 1000);

            // Mobile menu toggle
            const menuBtn = document.getElementById('mobileMenuBtn');
            const sidebarContent = document.getElementById('sidebarContent');
            if (menuBtn && sidebarContent) {
                menuBtn.addEventListener('click', () => {
                    sidebarContent.classList.toggle('hidden');
                });
            }

            // Desktop Collapsible Sidebar Logic
            const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
            const sidebar = document.getElementById('sidebar');
            
            // Restore collapsed state on boot
            if (localStorage.getItem('sidebar-collapsed') === 'true' && sidebar) {
                sidebar.classList.add('collapsed');
            }

            if (toggleSidebarBtn && sidebar) {
                toggleSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebar-collapsed', isCollapsed);
                });
            }

            // Asegurar que el menú móvil se cierre al navegar en pantallas pequeñas (< 1024px)
            if (sidebarContent && window.innerWidth < 1024) {
                sidebarContent.classList.add('hidden');
            }

            // --- LÓGICA DE SINCRONIZACIÓN AUTOMÁTICA ---
            const lastSyncEl = document.getElementById('lastSyncTimeText');
            
            // Cargar última sincronización guardada
            const storedTime = localStorage.getItem('last-sync-time');
            const storedTimestamp = localStorage.getItem('last-sync-timestamp');
            if (storedTime && lastSyncEl) {
                lastSyncEl.textContent = storedTime;
            }

            // Si Laravel reporta éxito de sincronización manual, guardar hora actual
            @if(session('success') && (str_contains(session('success'), 'Sincronización') || str_contains(session('success'), 'sincronizados')))
                const manualNow = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const manualNowStr = `${pad(manualNow.getDate())}/${pad(manualNow.getMonth()+1)}/${manualNow.getFullYear()}, ${pad(manualNow.getHours())}:${pad(manualNow.getMinutes())}:${pad(manualNow.getSeconds())}`;
                localStorage.setItem('last-sync-time', manualNowStr);
                localStorage.setItem('last-sync-timestamp', Date.now().toString());
                if (lastSyncEl) lastSyncEl.textContent = manualNowStr;
            @endif

            // Función para sincronización en segundo plano por AJAX
            function triggerAutoSync() {
                const syncIcon = document.getElementById('syncIconSidebar');
                if (syncIcon) {
                    syncIcon.classList.add('animate-spin');
                }
                
                fetch("{{ route('dashboard.sync') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        localStorage.setItem('last-sync-time', data.synced_at);
                        localStorage.setItem('last-sync-timestamp', Date.now().toString());
                        if (lastSyncEl) {
                            lastSyncEl.textContent = data.synced_at;
                        }
                        
                        if (data.new_events > 0) {
                            showToast(data.message, 'success');
                            // Recargar la página después de 1 segundo si hay nuevos datos
                            setTimeout(() => {
                                location.reload();
                            }, 1200);
                        }
                    }
                })
                .catch(err => {
                    console.error('Error en sincronización automática:', err);
                })
                .finally(() => {
                    if (syncIcon) {
                        syncIcon.classList.remove('animate-spin');
                    }
                });
            }

            // Comprobar al cargar la página si ya pasó más de 1 minuto para sincronizar de inmediato
            const currentTimestamp = Date.now();
            const oneMinuteLimit = 60000;
            if (!storedTimestamp || (currentTimestamp - parseInt(storedTimestamp)) > oneMinuteLimit) {
                setTimeout(triggerAutoSync, 800);
            }

            // Iniciar intervalo de sincronización automática (Establecido a 1 minuto)
            // 1 minuto = 60000 milisegundos
            if (window.syncInterval) clearInterval(window.syncInterval);
            window.syncInterval = setInterval(triggerAutoSync, 60000);
        });
    </script>
    @livewireScripts
    <!-- MODAL EDITAR PERFIL / NOMBRE DE EMPRESA -->
    <div id="profileModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
                <div class="flex items-center justify-between p-4 md:p-5 border-b border-slate-200 rounded-t bg-slate-50">
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center">
                        <i data-lucide="building-2" class="w-5 h-5 mr-2 text-slate-900"></i>
                        Editar Nombre de la Empresa / Perfil
                    </h3>
                    <button type="button" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="profileModal">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form action="{{ route('profile.update') }}" method="POST" class="p-4 md:p-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nombre de la Empresa / Usuario *</label>
                        <input type="text" name="name" value="{{ Auth::user()->name }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" value="{{ Auth::user()->email }}" required class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Nueva Contraseña (opcional)</label>
                        <input type="password" name="password" placeholder="Dejar en blanco para conservar la actual" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-700 mb-1">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" placeholder="Repite la contraseña" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-2.5 font-medium">
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                        <button data-modal-hide="profileModal" type="button" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2.5 border border-slate-300">
                            Cancelar
                        </button>
                        <button type="submit" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-5 py-2.5 shadow-md">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
