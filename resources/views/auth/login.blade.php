@extends('layouts.guest')
@section('title', 'Iniciar Sesión')

@section('content')
<div class="min-h-screen flex">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-600 to-teal-700 items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute w-80 h-80 rounded-full bg-white top-[-10%] right-[-10%]"></div>
            <div class="absolute w-60 h-60 rounded-full bg-white bottom-[-5%] left-[-5%]"></div>
        </div>
        <div class="relative z-10 text-white text-center max-w-sm">
            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center font-black text-3xl mx-auto mb-8 border border-white/30">T</div>
            <h1 class="text-3xl font-extrabold mb-4">CRM-AC</h1>
            <p class="text-emerald-100 text-lg leading-relaxed">Gestiona tu inventario, controla tus finanzas y toma decisiones inteligentes para tu negocio.</p>
            <div class="mt-10 grid grid-cols-2 gap-4 text-left">
                <div class="bg-white/10 rounded-xl p-4 border border-white/20">
                    <div class="text-2xl font-bold">📦</div>
                    <p class="text-sm font-semibold mt-1">Sistema de Inventarios</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4 border border-white/20">
                    <div class="text-2xl font-bold">💰</div>
                    <p class="text-sm font-semibold mt-1">Sistema de Finanzas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <!-- Mobile logo -->
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white">T</div>
                <span class="text-xl font-bold text-slate-800">CRM-AC</span>
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Bienvenido de vuelta</h2>
            <p class="text-slate-500 mb-8">Inicia sesión para acceder a tu cuenta.</p>

            <!-- Status alerts -->
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-700">Contraseña</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">¿Olvidaste tu contraseña?</a>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-xl border @error('password') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="••••••••">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember -->
                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="remember" class="text-sm text-slate-600">Mantener sesión iniciada</label>
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all hover:-translate-y-0.5 shadow-sm shadow-emerald-500/20 text-sm">
                    Iniciar sesión
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-700 font-semibold">Regístrate</a>
            </p>
        </div>
    </div>
</div>
@endsection
