@extends('layouts.guest')
@section('title', 'Recuperar Contraseña')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-2 mb-10">
            <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white">T</div>
            <span class="text-xl font-bold text-slate-800">CRM-AC</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="mb-6">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 mb-1">Recuperar contraseña</h2>
                <p class="text-sm text-slate-500">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>

            <!-- Status message (link sent) -->
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all hover:-translate-y-0.5 shadow-sm text-sm">
                    Enviar enlace de recuperación
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors">
                    ← Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
