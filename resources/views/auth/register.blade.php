@extends('layouts.guest')
@section('title', 'Crear Cuenta')

@section('content')
<div class="min-h-screen flex">

    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-slate-900 items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 70% 40%, #10b981 0%, transparent 55%), radial-gradient(circle at 20% 80%, #6d28d9 0%, transparent 50%);"></div>
        <div class="relative z-10 text-white max-w-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-xl mb-8">T</div>
            <h1 class="text-3xl font-bold mb-3 text-white">CRM-AC</h1>
            <p class="text-slate-400 text-base leading-relaxed">Únete y comienza a transformar la gestión de tu negocio hoy mismo.</p>
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

            <h2 class="text-2xl font-bold text-slate-900 dark:text-zinc-50 mb-1">Crear cuenta</h2>
            <p class="text-slate-500 dark:text-zinc-400 mb-8 text-sm">Completa el formulario para comenzar.</p>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                @php
                $inputClass = 'w-full px-4 py-3 rounded-xl border text-sm transition border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent';
                $inputErrorClass = 'w-full px-4 py-3 rounded-xl border text-sm transition border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent';
                $labelClass = 'block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1.5';
                @endphp

                <!-- Name -->
                <div>
                    <label for="name" class="{{ $labelClass }}">Nombre completo</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                        class="{{ $errors->has('name') ? $inputErrorClass : $inputClass }}"
                        placeholder="Juan Pérez">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                        class="{{ $errors->has('email') ? $inputErrorClass : $inputClass }}"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="{{ $labelClass }}">Teléfono <span class="text-slate-400 dark:text-zinc-600 font-normal">(opcional)</span></label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                        class="{{ $inputClass }}"
                        placeholder="+52 55 0000 0000">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="{{ $labelClass }}">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="{{ $errors->has('password') ? $inputErrorClass : $inputClass }}"
                        placeholder="Mínimo 8 caracteres">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="{{ $errors->has('password_confirmation') ? $inputErrorClass : $inputClass }}"
                        placeholder="Repite la contraseña">
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white font-semibold rounded-xl transition-all text-sm">
                    Crear cuenta
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500 dark:text-zinc-500">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold">Iniciar sesión</a>
            </p>
        </div>
    </div>
</div>
@endsection
