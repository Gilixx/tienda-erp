@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Panel Principal')

@section('content')

    <!-- Welcome Banner -->
    <div class="bg-slate-900 dark:bg-zinc-800 rounded-2xl p-7 text-white mb-8 relative overflow-hidden">
        <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle at 80% 50%, #fff 0%, transparent 60%);"></div>
        <div class="relative z-10">
            <p class="text-slate-400 dark:text-zinc-500 text-xs font-medium mb-2 uppercase tracking-widest">{{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
            <h2 class="text-xl font-semibold mb-1 text-white">Bienvenido, {{ $user->name }}</h2>
            <p class="text-slate-400 dark:text-zinc-500 text-sm">
                @if($user->isAdmin())
                    Acceso de <span class="text-white font-medium">administrador</span> a todos los módulos del sistema.
                @else
                    Tienes <span class="text-white font-medium">{{ $services->count() }} {{ Str::plural('módulo', $services->count()) }}</span> activo(s) en tu cuenta.
                @endif
            </p>
        </div>
    </div>

    <!-- Module Cards -->
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Módulos disponibles</h3>
        <span class="text-xs text-slate-400 dark:text-zinc-500 font-mono">{{ $services->count() }} activo(s)</span>
    </div>

    @if($services->isEmpty())
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-12 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-slate-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <p class="text-slate-700 dark:text-zinc-300 font-medium text-sm mb-1">Sin módulos activos</p>
            <p class="text-xs text-slate-400 dark:text-zinc-500">Contacta al administrador para contratar un servicio.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($services as $service)
                @php
                    $href = match($service->key) {
                        'inventory' => route('inventory'),
                        'finance'   => route('finance'),
                        default     => '#',
                    };
                    $colorClass = match($service->key) {
                        'inventory' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
                        'finance'   => 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
                        default     => 'bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400',
                    };
                    $borderAccent = match($service->key) {
                        'inventory' => 'border-l-[3px] border-l-emerald-500',
                        'finance'   => 'border-l-[3px] border-l-amber-500',
                        default     => 'border-l-[3px] border-l-slate-300 dark:border-l-zinc-600',
                    };
                @endphp
                <a href="{{ $href }}"
                   class="group bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 {{ $borderAccent }} rounded-2xl p-6 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-150 flex flex-col gap-5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl {{ $colorClass }} flex items-center justify-center">
                            @if($service->key === 'inventory')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            @elseif($service->key === 'finance')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            @endif
                        </div>
                        <svg class="h-4 w-4 text-slate-300 dark:text-zinc-600 group-hover:text-slate-500 dark:group-hover:text-zinc-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 dark:text-zinc-50 mb-1 text-sm">{{ $service->name }}</h4>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">{{ $service->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

@endsection
