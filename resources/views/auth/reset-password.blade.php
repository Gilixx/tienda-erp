@extends('layouts.guest')
@section('title', 'Nueva Contraseña')

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
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-xl font-extrabold text-slate-900 mb-1">Restablecer contraseña</h2>
                <p class="text-sm text-slate-500">Elige una contraseña segura para tu cuenta.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <!-- Token (hidden) -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                        class="w-full px-4 py-3 rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="tu@empresa.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Nueva contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl border @error('password') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="Mínimo 8 caracteres">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirmar nueva contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl border @error('password_confirmation') border-red-400 bg-red-50 @else border-slate-200 bg-white @enderror text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm"
                        placeholder="Repite la contraseña">
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all hover:-translate-y-0.5 shadow-sm text-sm">
                    Guardar nueva contraseña
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
