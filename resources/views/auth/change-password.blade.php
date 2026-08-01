@extends('layouts.guest')
@section('title', 'Cambiar contraseña')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-slate-50 dark:bg-zinc-950">
    <div class="w-full max-w-md bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-8 shadow-sm">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white">T</div>
            <span class="text-xl font-bold text-slate-900 dark:text-zinc-50">CRM-AC</span>
        </div>

        <h2 class="text-xl font-bold text-slate-900 dark:text-zinc-50 mb-1">Actualiza tu contraseña</h2>
        <p class="text-slate-500 dark:text-zinc-400 mb-6 text-sm">
            Por seguridad, el administrador restableció tu contraseña. Establece una nueva para continuar.
        </p>

        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1.5">Nueva contraseña</label>
                <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl border text-sm transition
                    @error('password') border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/20 @else border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 @enderror
                    text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500
                    focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    placeholder="••••••••">
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1.5">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm transition
                    text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500
                    focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    placeholder="••••••••">
            </div>

            <button type="submit"
                class="w-full py-3 px-6 bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white font-semibold rounded-xl transition-all text-sm">
                Guardar y continuar
            </button>
        </form>
    </div>
</div>
@endsection
