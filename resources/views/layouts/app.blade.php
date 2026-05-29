<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CRM-AC</title>

    {{-- Anti-flash: aplica dark antes de que cargue el CSS --}}
    <script>if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-slate-50 dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 antialiased min-h-screen flex transition-colors duration-200">

    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-zinc-900 border-r border-slate-200/70 dark:border-zinc-800 min-h-screen flex flex-col flex-shrink-0">

        <!-- Logo -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-zinc-800 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white text-sm">T</div>
            <span class="text-base font-semibold text-slate-900 dark:text-zinc-50 tracking-tight">CRM-AC</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-5 space-y-0.5">

            <p class="px-3 pt-1 pb-2 text-[10px] font-semibold text-slate-400 dark:text-zinc-600 uppercase tracking-widest">General</p>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 py-2 pr-3 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('dashboard')
                    ? 'bg-emerald-50 dark:bg-emerald-900/25 text-emerald-700 dark:text-emerald-400 border-l-[3px] border-emerald-600 dark:border-emerald-500 pl-[9px]'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-slate-800 dark:hover:text-zinc-100 border-l-[3px] border-transparent pl-[9px]' }}">
                <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Inicio
            </a>

            @if(auth()->user()->hasService('inventory'))
            <p class="px-3 pt-4 pb-2 text-[10px] font-semibold text-slate-400 dark:text-zinc-600 uppercase tracking-widest">Inventarios</p>

            <a href="{{ route('inventory') }}"
               class="flex items-center gap-3 py-2 pr-3 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('inventory')
                    ? 'bg-emerald-50 dark:bg-emerald-900/25 text-emerald-700 dark:text-emerald-400 border-l-[3px] border-emerald-600 dark:border-emerald-500 pl-[9px]'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-slate-800 dark:hover:text-zinc-100 border-l-[3px] border-transparent pl-[9px]' }}">
                <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Inventario
            </a>

            <a href="{{ route('inventory.stats') }}"
               class="flex items-center gap-3 py-2 pr-3 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('inventory.stats')
                    ? 'bg-violet-50 dark:bg-violet-900/25 text-violet-700 dark:text-violet-400 border-l-[3px] border-violet-600 dark:border-violet-500 pl-[9px]'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-slate-800 dark:hover:text-zinc-100 border-l-[3px] border-transparent pl-[9px]' }}">
                <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Estadísticas IA
            </a>
            @endif

            @if(auth()->user()->hasService('finance'))
            <p class="px-3 pt-4 pb-2 text-[10px] font-semibold text-slate-400 dark:text-zinc-600 uppercase tracking-widest">Finanzas</p>

            <a href="{{ route('finance') }}"
               class="flex items-center gap-3 py-2 pr-3 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('finance')
                    ? 'bg-amber-50 dark:bg-amber-900/25 text-amber-700 dark:text-amber-400 border-l-[3px] border-amber-600 dark:border-amber-500 pl-[9px]'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-slate-800 dark:hover:text-zinc-100 border-l-[3px] border-transparent pl-[9px]' }}">
                <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Finanzas
            </a>
            @endif

        </nav>

        <!-- User info + logout -->
        <div class="px-3 py-4 border-t border-slate-100 dark:border-zinc-800">
            <div class="flex items-center gap-3 px-3 mb-2">
                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-semibold text-xs flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-zinc-100 truncate leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 dark:text-zinc-500 truncate leading-tight">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full text-left flex items-center gap-2 py-2 pr-3 rounded-lg text-xs font-medium text-slate-500 dark:text-zinc-500 hover:bg-slate-50 dark:hover:bg-zinc-800 hover:text-red-600 dark:hover:text-red-400 transition-colors border-l-[3px] border-transparent pl-[9px]">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 min-h-screen overflow-auto min-w-0">

        <!-- Top bar -->
        <header class="bg-white dark:bg-zinc-900 border-b border-slate-200/70 dark:border-zinc-800 px-8 py-4 flex items-center justify-between sticky top-0 z-10">
            <h1 class="text-base font-semibold text-slate-900 dark:text-zinc-50 tracking-tight">@yield('page-title', 'Dashboard')</h1>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold tracking-wide
                    {{ auth()->user()->isAdmin()
                        ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                        : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400' }}">
                    {{ auth()->user()->isAdmin() ? 'Administrador' : 'Usuario' }}
                </span>

                {{-- Botón modo oscuro --}}
                <button onclick="toggleTheme()"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
                    title="Cambiar tema">
                    {{-- Luna (modo claro → mostrar para ir a oscuro) --}}
                    <svg class="w-[18px] h-[18px] dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    {{-- Sol (modo oscuro → mostrar para ir a claro) --}}
                    <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </header>

        <div class="p-8">

            {{-- Flash messages --}}
            @if(session('error'))
                <div class="mb-6 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/60 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @yield('scripts')
</body>
</html>
