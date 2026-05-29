@extends('layouts.guest')
@section('title', 'Iniciar Sesión')

@section('content')
<div class="min-h-screen flex">

    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-slate-900 items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 70% 40%, #10b981 0%, transparent 55%), radial-gradient(circle at 20% 80%, #6d28d9 0%, transparent 50%);"></div>
        <div class="relative z-10 text-white max-w-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-xl mb-8">T</div>
            <h1 class="text-3xl font-bold mb-3 text-white">CRM-AC</h1>
            <p class="text-slate-400 text-base leading-relaxed mb-10">Gestiona tu inventario, controla tus finanzas y toma decisiones inteligentes.</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-emerald-900/50 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-white">Sistema de Inventarios</p>
                    <p class="text-xs text-slate-500 mt-0.5">Control de stock y movimientos</p>
                </div>
                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-amber-900/50 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-white">Sistema de Finanzas</p>
                    <p class="text-xs text-slate-500 mt-0.5">Ingresos, egresos y flujo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-white dark:bg-zinc-900 relative">

        {{-- Botón modo oscuro --}}
        <button onclick="toggleTheme()"
            class="absolute top-5 right-5 w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 dark:text-zinc-500 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
            title="Cambiar tema">
            <svg class="w-[18px] h-[18px] dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        <div class="w-full max-w-md">
            <!-- Mobile logo -->
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white">T</div>
                <span class="text-xl font-bold text-slate-900 dark:text-zinc-50">CRM-AC</span>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 dark:text-zinc-50 mb-1">Bienvenido de vuelta</h2>
            <p class="text-slate-500 dark:text-zinc-400 mb-8 text-sm">Inicia sesión para acceder a tu cuenta.</p>

            <!-- Status alerts -->
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl border text-sm transition
                        @error('email') border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/20 @else border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 @enderror
                        text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500
                        focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-zinc-300">Contraseña</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">¿Olvidaste tu contraseña?</a>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-xl border text-sm transition
                        @error('password') border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/20 @else border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 @enderror
                        text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500
                        focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        placeholder="••••••••">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember -->
                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember"
                        class="w-4 h-4 rounded border-slate-300 dark:border-zinc-600 text-emerald-600 focus:ring-emerald-500 dark:bg-zinc-700">
                    <label for="remember" class="text-sm text-slate-600 dark:text-zinc-400">Mantener sesión iniciada</label>
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white font-semibold rounded-xl transition-all text-sm">
                    Iniciar sesión
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500 dark:text-zinc-500">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold">Regístrate</a>
            </p>
        </div>
    </div>
</div>
@endsection
