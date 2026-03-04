@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Panel Principal')

@section('content')
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-8 text-white mb-8 relative overflow-hidden">
        <div class="absolute right-0 top-0 h-full w-1/2 opacity-10">
            <div class="absolute w-64 h-64 rounded-full bg-white -top-16 -right-16"></div>
            <div class="absolute w-40 h-40 rounded-full bg-white bottom-0 right-32"></div>
        </div>
        <div class="relative z-10">
            <p class="text-emerald-100 text-sm font-medium mb-1">{{ now()->format('l, d \d\e F Y') }}</p>
            <h2 class="text-2xl font-extrabold mb-1">Hola, {{ $user->name }}! 👋</h2>
            <p class="text-emerald-100">
                @if($user->isAdmin())
                    Tienes acceso de <strong>administrador</strong> a todos los módulos del sistema.
                @else
                    Tienes acceso a <strong>{{ $services->count() }}</strong> {{ Str::plural('módulo', $services->count()) }} contratado(s).
                @endif
            </p>
        </div>
    </div>

    <!-- Module Cards -->
    <h3 class="text-base font-semibold text-slate-700 mb-4">Módulos disponibles</h3>

    @if($services->isEmpty())
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-10 text-center">
            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
            </div>
            <p class="text-slate-600 font-medium mb-1">Sin módulos activos</p>
            <p class="text-sm text-slate-400">Contacta al administrador para contratar un servicio.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($services as $service)
                @php
                    $href = match($service->key) {
                        'inventory' => route('inventory'),
                        'finance'   => route('finance'),
                        default     => '#',
                    };
                    $colorClass = match($service->key) {
                        'inventory' => 'bg-emerald-100 text-emerald-700',
                        'finance'   => 'bg-orange-100 text-orange-700',
                        default     => 'bg-slate-100 text-slate-700',
                    };
                    $emoji = match($service->key) {
                        'inventory' => '📦',
                        'finance'   => '💰',
                        default     => '⚙️',
                    };
                @endphp
                <a href="{{ $href }}" class="group bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md hover:-translate-y-1 transition-all duration-200 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-xl {{ $colorClass }} flex items-center justify-center text-xl">{{ $emoji }}</div>
                        <svg class="h-5 w-5 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-800 mb-1">{{ $service->name }}</h4>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ $service->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
