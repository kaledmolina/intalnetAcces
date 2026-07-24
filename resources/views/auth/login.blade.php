<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - IntalnetAcces</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Flowbite & Tailwind CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
        }
        .font-heading {
            font-family: 'Outfit', sans-serif;
        }
        .grayscale-logo {
            filter: grayscale(100%) contrast(1.1);
            mix-blend-mode: multiply;
        }
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
    </style>
</head>
<body class="h-full flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white border-2 border-black rounded-2xl p-8 shadow-2xl space-y-6 relative overflow-hidden">
        <!-- Logo and Header -->
        <div class="text-center space-y-3">
            <div class="relative inline-block">
                <img src="{{ asset('images/logo.png') }}" class="h-20 mx-auto object-contain rounded-2xl shadow-sm border border-slate-200 p-1 filter grayscale" alt="IntalnetAcces Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').classList.remove('hidden');">
                <div id="logoFallback" class="hidden w-16 h-16 bg-black rounded-2xl flex items-center justify-center text-white shadow-xl mx-auto mb-2 border border-slate-800">
                    <i data-lucide="shield-check" class="w-9 h-9"></i>
                </div>
            </div>
            
            <div class="space-y-1">
                <h1 class="font-heading font-black text-2xl text-slate-900 tracking-tight">IntalnetAcces</h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Plataforma de Control de Asistencia</p>
            </div>
        </div>

        <!-- Session Status / Errors -->
        @if(session('success'))
            <div class="p-3 bg-slate-100 border border-black text-slate-950 text-xs font-bold rounded-xl flex items-center space-x-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-slate-950 flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 bg-slate-100 border border-red-500 text-red-700 text-xs font-semibold rounded-xl space-y-1">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center space-x-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-[10px] font-black uppercase text-slate-700 tracking-wider mb-1">Correo Electrónico</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="ejemplo@intalnet.com" class="ps-10 bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-3 font-medium">
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-[10px] font-black uppercase text-slate-700 tracking-wider mb-1">Contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <input type="password" name="password" id="password" required placeholder="••••••••" class="ps-10 bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-xl focus:ring-black focus:border-black block w-full p-3 font-medium">
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-black border-slate-300 rounded focus:ring-black focus:ring-offset-0 focus:ring-1">
                <label for="remember" class="ms-2 text-xs font-bold text-slate-600">Mantener sesión iniciada</label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-hover-grow w-full py-3 px-5 text-white bg-black hover:bg-slate-800 font-extrabold text-sm rounded-xl shadow-md flex items-center justify-center space-x-2">
                <span>Ingresar al Sistema</span>
                <i data-lucide="arrow-right" class="w-4 h-4 text-white"></i>
            </button>

            <!-- Register Link -->
            <div class="pt-2 text-center border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-600">
                    ¿No tienes una cuenta? 
                    <a href="{{ route('register') }}" class="font-extrabold text-black hover:underline">Regístrate aquí</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        // Inicializar iconos de Lucide
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
