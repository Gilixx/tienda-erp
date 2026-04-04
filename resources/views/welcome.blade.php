<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Tienda ERP') }} - Finanzas e Inventario</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
            .glassmorphism {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
            .text-gradient {
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .blob {
                position: absolute;
                filter: blur(80px);
                z-index: 0;
                opacity: 0.6;
                animation: float 10s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px) scale(1); }
                50% { transform: translateY(-20px) scale(1.05); }
                100% { transform: translateY(0px) scale(1); }
            }
        </style>
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased relative overflow-x-hidden selection:bg-emerald-500/30">
        <div class="relative flex flex-col min-h-screen overflow-hidden">
            <!-- Background Blobs -->
            <div class="blob bg-orange-200/50 w-96 h-96 rounded-full top-[-10%] left-[-10%]"></div>
            <div class="blob bg-emerald-200/50 w-[500px] h-[500px] rounded-full bottom-[-20%] right-[-10%] mix-blend-multiply" style="animation-delay: 2s;"></div>
            <div class="blob bg-amber-100/50 w-[400px] h-[400px] rounded-full top-[40%] left-[20%] mix-blend-multiply" style="animation-delay: 4s;"></div>
            <!-- Navbar -->
            <nav class="w-full px-6 py-4 flex items-center justify-between glassmorphism sticky top-0 z-50 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white shadow-md">
                        T
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-800">CRM-AC</span>
                </div>
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                    <a href="#features" class="hover:text-emerald-600 transition-colors duration-200">Módulos</a>
                    <a href="#benefits" class="hover:text-emerald-600 transition-colors duration-200">Beneficios</a>
                    <a href="#testimonials" class="hover:text-emerald-600 transition-colors duration-200">Testimonios</a>
                </div>
                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-slate-800 hover:text-emerald-600 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-emerald-600 transition-colors">Iniciar sesión</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-medium px-4 py-2 rounded-full bg-emerald-600 text-white hover:bg-emerald-700 transition-transform hover:scale-105 duration-200 shadow-md transform">Comenzar gratis</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </nav>

            <!-- Hero Section -->
            <main class="flex items-center justify-center px-6 py-20 lg:py-32 relative">
                <div class="max-w-5xl mx-auto text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-xs font-medium text-emerald-600 mb-8 border border-emerald-200 shadow-sm">
                        <span class="flex w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Solución profesional para tu empresa
                    </div>
                    <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-8 leading-tight text-slate-900">
                        Gestiona tu <span class="text-gradient bg-gradient-to-r from-emerald-500 to-teal-600">Inventario</span> y <br class="hidden md:block"/>
                        controla tus <span class="text-gradient bg-gradient-to-r from-amber-500 to-orange-500">Finanzas</span>
                    </h1>
                    <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto mb-12 leading-relaxed">
                        Un sistema integral y seguro diseñado para negocios exigentes. Optimiza tu administración, asegura tus datos y toma decisiones estratégicas con nuestra interfaz intuitiva.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="px-8 py-4 rounded-full bg-emerald-600 text-white font-semibold text-lg hover:bg-emerald-700 hover:shadow-lg transition-all hover:-translate-y-1 duration-300 flex items-center gap-2 shadow-emerald-500/30">
                            Comenzar ahora
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#features" class="px-8 py-4 rounded-full bg-white text-slate-700 border border-slate-200 font-semibold text-lg hover:bg-slate-50 hover:shadow-md transition-all duration-300">
                            Ver módulos
                        </a>
                    </div>
                </div>
            </main>

            <!-- Features Dashboard Mockup -->
            <section class="max-w-6xl mx-auto px-6 pb-24 relative z-10">
                <div class="rounded-2xl bg-white/50 p-2 relative group overflow-hidden shadow-2xl border border-slate-200 backdrop-blur-sm">
                    <div class="bg-white rounded-xl overflow-hidden border border-slate-200 flex flex-col h-[500px] shadow-sm">
                        <!-- Window header -->
                        <div class="h-10 bg-slate-100 flex items-center px-4 border-b border-slate-200 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                        </div>
                        <!-- Mockup Content -->
                        <div class="flex-1 p-6 flex gap-6 overflow-hidden bg-slate-50">
                            <!-- Sidebar -->
                            <div class="w-48 hidden md:flex flex-col gap-4 border-r border-slate-200 pr-4">
                                <div class="h-8 rounded bg-emerald-100 w-full mb-4"></div>
                                <div class="h-6 rounded bg-slate-200 w-3/4"></div>
                                <div class="h-6 rounded bg-slate-200 w-5/6"></div>
                                <div class="h-6 rounded bg-slate-200 w-2/3"></div>
                            </div>
                            <!-- Main area -->
                            <div class="flex-1 flex flex-col gap-6">
                                <!-- Top widgets -->
                                <div class="flex gap-4">
                                    <div class="flex-1 h-32 rounded-xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col justify-between relative overflow-hidden">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 absolute -top-2 -right-2"></div>
                                        <div class="h-4 w-1/2 bg-slate-200 rounded"></div>
                                        <div class="h-8 w-3/4 bg-emerald-400 rounded"></div>
                                    </div>
                                    <div class="flex-1 h-32 rounded-xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col justify-between relative overflow-hidden">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 absolute -top-2 -right-2"></div>
                                        <div class="h-4 w-1/2 bg-slate-200 rounded"></div>
                                        <div class="h-8 w-2/3 bg-amber-400 rounded"></div>
                                    </div>
                                    <div class="flex-1 h-32 rounded-xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col justify-between hidden sm:flex">
                                        <div class="h-4 w-1/2 bg-slate-200 rounded"></div>
                                        <div class="h-8 w-3/4 bg-teal-400 rounded"></div>
                                    </div>
                                </div>
                                <!-- Chart area -->
                                <div class="flex-1 rounded-xl bg-white border border-slate-200 shadow-sm p-4 flex items-end gap-2">
                                    @for ($i = 0; $i < 12; $i++)
                                        <div class="flex-1 bg-gradient-to-t from-emerald-100 to-emerald-300 rounded-t-sm" style="height: {{ rand(30, 100) }}%"></div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Grid -->
            <section id="features" class="py-24 bg-white relative z-10 border-t border-slate-200">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-slate-800">Módulos del Sistema</h2>
                        <p class="text-slate-600 max-w-2xl mx-auto text-lg">Todo lo que necesitas para operar, en una sola plataforma integrada, diseñada para potenciar tu productividad.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Feature 1: Inventario -->
                        <div class="bg-slate-50 border border-slate-100 p-8 rounded-2xl hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                            <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center mb-6 group-hover:bg-emerald-200 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-slate-800">Sistema de Inventarios</h3>
                            <p class="text-slate-600 leading-relaxed">Gestión completa de existencias, control de variantes, alertas de reabastecimiento y movimientos de almacén en tiempo real. Optimiza tu cadena de suministro.</p>
                        </div>

                        <!-- Feature 2: Finanzas -->
                        <div class="bg-slate-50 border border-slate-100 p-8 rounded-2xl hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                            <div class="w-14 h-14 rounded-xl bg-orange-100 flex items-center justify-center mb-6 group-hover:bg-orange-200 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-slate-800">Sistema de Finanzas</h3>
                            <p class="text-slate-600 leading-relaxed">Control de ingresos, gastos, cuentas por cobrar y pagar. Visualiza el flujo de caja e informes financieros de manera automatizada para decisiones seguras.</p>
                        </div>

                        <!-- Feature 3: Login para usuarios -->
                        <div class="bg-slate-50 border border-slate-100 p-8 rounded-2xl hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                            <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center mb-6 group-hover:bg-blue-200 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.092 2.027-.273 3.011m-1.42 2.58A14.07 14.07 0 0112 21m-4.25-1.55a10.02 10.02 0 01-1.35-4.57m3.43-1.6c.07-.15.13-.3.19-.45" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-slate-800">Acceso Seguro y Roles</h3>
                            <p class="text-slate-600 leading-relaxed">Sistema de login robusto con gestión de usuarios, roles y permisos. Protege tu información y controla a qué módulos tiene acceso cada miembro de tu equipo.</p>
                        </div>

                        <!-- Feature 4: Interfaz del Sistema -->
                        <div class="bg-slate-50 border border-slate-100 p-8 rounded-2xl hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                            <div class="w-14 h-14 rounded-xl bg-teal-100 flex items-center justify-center mb-6 group-hover:bg-teal-200 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-slate-800">Interfaz Intuitiva</h3>
                            <p class="text-slate-600 leading-relaxed">Un dashboard moderno, limpio y fácil de usar en cualquier dispositivo. Accede a métricas clave, reportes y herramientas de trabajo en un diseño centrado en la usabilidad.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-24 relative z-10 bg-slate-50">
                <div class="max-w-4xl mx-auto px-6 text-center">
                    <div class="bg-white rounded-3xl p-12 relative overflow-hidden border border-emerald-100 shadow-xl shadow-emerald-900/5">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-orange-50/50"></div>
                        <div class="relative z-10">
                            <h2 class="text-3xl md:text-5xl font-extrabold mb-6 text-slate-900">¿Listo para profesionalizar tu gestión?</h2>
                            <p class="text-xl text-slate-600 mb-8 max-w-2xl mx-auto">Comienza ahora e impulsa el crecimiento de tu empresa con una herramienta hecha a tu medida.</p>
                            <a href="{{ Route::has('register') ? route('register') : '#' }}" class="inline-block px-8 py-4 rounded-full bg-emerald-600 text-white font-bold text-lg hover:bg-emerald-700 transition-transform hover:-translate-y-1 duration-200 shadow-lg shadow-emerald-500/20">
                                Contactar para Demo
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="py-12 border-t border-slate-200 mt-auto relative z-10 bg-white">
                <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-emerald-600 flex items-center justify-center font-bold text-white text-sm">
                            T
                        </div>
                        <span class="font-bold text-slate-800 text-lg">CRM-AC</span>
                    </div>
                    <p class="text-sm text-slate-500">© {{ date('Y') }} CRM-AC. Todos los derechos reservados.</p>
                    <div class="flex gap-6">
                        <a href="#" class="text-slate-500 hover:text-emerald-600 transition-colors text-sm font-medium">Términos</a>
                        <a href="#" class="text-slate-500 hover:text-emerald-600 transition-colors text-sm font-medium">Privacidad</a>
                        <a href="#" class="text-slate-500 hover:text-emerald-600 transition-colors text-sm font-medium">Soporte</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>