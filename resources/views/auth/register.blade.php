@extends('layouts.guest')
@section('title', 'Crear Cuenta')

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
            <p class="text-emerald-100 text-lg leading-relaxed">Únete y comienza a transformar la gestión de tu negocio hoy mismo.</p>
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

            <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Crear cuenta</h2>
            <p class="text-slate-500 mb-8">Completa el formulario para comenzar.</p>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                {{-- Honeypot anti-bot: campo invisible que solo los bots llenan --}}
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nombre completo</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                        class="w-full px-4 py-3 rounded-xl border @error('name') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="Juan Pérez">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Teléfono <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="+52 55 0000 0000">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl border @error('password') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="Mínimo 8 caracteres">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl border @error('password_confirmation') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="Repite la contraseña">
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all hover:-translate-y-0.5 shadow-sm shadow-emerald-500/20 text-sm">
                    Crear cuenta
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="text-emerald-600 hover:text-emerald-700 font-semibold">Iniciar sesión</a>
            </p>
        </div>
    </div>
</div>
@endsection
