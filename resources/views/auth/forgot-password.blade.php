@extends('layouts.guest')
@section('title', 'Recuperar Contraseña')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50 dark:bg-zinc-950 relative">

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
        <!-- Logo -->
        <div class="flex items-center justify-center gap-2 mb-10">
            <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white">T</div>
            <span class="text-xl font-bold text-slate-900 dark:text-zinc-50">CRM-AC</span>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 p-8">
            <div class="mb-6">
                <div class="w-11 h-11 rounded-xl bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center mb-4">
                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-zinc-50 mb-1">Recuperar contraseña</h2>
                <p class="text-sm text-slate-500 dark:text-zinc-400">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>

            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 rounded-xl border text-sm transition
                        @error('email') border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/20 @else border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 @enderror
                        text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500
                        focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white font-semibold rounded-xl transition-all text-sm">
                    Enviar enlace de recuperación
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-slate-500 dark:text-zinc-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
