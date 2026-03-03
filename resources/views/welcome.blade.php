<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Tienda ERP') }} - Finanzas e Inventario</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <style>
            body { font-family: 'Inter', sans-serif; }
            .glassmorphism {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.05);
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
    <body class="bg-gray-950 text-gray-100 antialiased relative overflow-x-hidden selection:bg-indigo-500/30">
        <!-- Background Blobs -->
        <div class="blob bg-indigo-600/30 w-96 h-96 rounded-full top-[-10%] left-[-10%]"></div>
        <div class="blob bg-emerald-600/20 w-[500px] h-[500px] rounded-full bottom-[-20%] right-[-10%] mix-blend-screen" style="animation-delay: 2s;"></div>
        <div class="blob bg-purple-600/20 w-[400px] h-[400px] rounded-full top-[40%] left-[20%] mix-blend-screen" style="animation-delay: 4s;"></div>

        <div class="relative z-10 flex flex-col min-h-screen">
            <!-- Navbar -->
            <nav class="w-full px-6 py-4 flex items-center justify-between glassmorphism sticky top-0 z-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg">
                        T
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">TiendaERP</span>
                </div>
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-300">
                    <a href="#features" class="hover:text-white transition-colors duration-200">Características</a>
                    <a href="#benefits" class="hover:text-white transition-colors duration-200">Beneficios</a>
                    <a href="#testimonials" class="hover:text-white transition-colors duration-200">Testimonios</a>
                </div>
                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-white hover:text-indigo-400 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-300 hover:text-white transition-colors">Iniciar sesión</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-medium px-4 py-2 rounded-full bg-white text-gray-900 hover:bg-gray-100 transition-transform hover:scale-105 duration-200 shadow-[0_0_15px_rgba(255,255,255,0.2)]">Comenzar gratis</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </nav>

            <!-- Hero Section -->
            <main class="flex-grow flex items-center justify-center px-6 py-20 lg:py-32 relative">
                <div class="max-w-5xl mx-auto text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glassmorphism text-xs font-medium text-indigo-300 mb-8 border border-indigo-500/30">
                        <span class="flex w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        Gestión total para tu negocio
                    </div>
                    <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8 leading-tight">
                        Domina tus <span class="text-gradient bg-gradient-to-r from-emerald-400 to-teal-500">Finanzas</span> y <br class="hidden md:block"/>
                        controla tu <span class="text-gradient bg-gradient-to-r from-indigo-400 to-purple-500">Inventario</span>
                    </h1>
                    <p class="text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                        El sistema definitivo diseñado para emprendedores y negocios que buscan escalar. Automatiza procesos, mejora tu rentabilidad y toma decisiones basadas en datos reales.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="px-8 py-4 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold text-lg hover:shadow-[0_0_30px_rgba(99,102,241,0.5)] transition-all hover:scale-105 duration-300 flex items-center gap-2">
                            Crear cuenta gratis
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#features" class="px-8 py-4 rounded-full glassmorphism text-white font-semibold text-lg hover:bg-white/10 transition-all duration-300">
                            Descubrir más
                        </a>
                    </div>
                </div>
            </main>

            <!-- Features Dashboard Mockup -->
            <section class="max-w-6xl mx-auto px-6 pb-24 relative z-10">
                <div class="rounded-2xl glassmorphism p-2 relative group overflow-hidden shadow-2xl shadow-indigo-500/10 border border-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-transparent to-transparent z-10"></div>
                    <div class="bg-gray-900 rounded-xl overflow-hidden border border-gray-800 flex flex-col h-[500px]">
                        <!-- Window header -->
                        <div class="h-10 bg-gray-800/50 flex items-center px-4 border-b border-gray-700/50 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <!-- Mockup Content -->
                        <div class="flex-1 p-6 flex gap-6 overflow-hidden">
                            <!-- Sidebar -->
                            <div class="w-48 hidden md:flex flex-col gap-4 border-r border-gray-800 pr-4">
                                <div class="h-8 rounded bg-indigo-500/20 w-full mb-4"></div>
                                <div class="h-6 rounded bg-gray-800 w-3/4"></div>
                                <div class="h-6 rounded bg-gray-800 w-5/6"></div>
                                <div class="h-6 rounded bg-gray-800 w-2/3"></div>
                            </div>
                            <!-- Main area -->
                            <div class="flex-1 flex flex-col gap-6">
                                <!-- Top widgets -->
                                <div class="flex gap-4">
                                    <div class="flex-1 h-32 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700/50 p-4 flex flex-col justify-between relative overflow-hidden">
                                        <div class="w-10 h-10 rounded-full bg-emerald-500/20 absolute -top-2 -right-2"></div>
                                        <div class="h-4 w-1/2 bg-gray-700 rounded"></div>
                                        <div class="h-8 w-3/4 bg-emerald-500/80 rounded"></div>
                                    </div>
                                    <div class="flex-1 h-32 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700/50 p-4 flex flex-col justify-between relative overflow-hidden">
                                        <div class="w-10 h-10 rounded-full bg-indigo-500/20 absolute -top-2 -right-2"></div>
                                        <div class="h-4 w-1/2 bg-gray-700 rounded"></div>
                                        <div class="h-8 w-2/3 bg-indigo-500/80 rounded"></div>
                                    </div>
                                    <div class="flex-1 h-32 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700/50 p-4 flex flex-col justify-between hidden sm:flex">
                                        <div class="h-4 w-1/2 bg-gray-700 rounded"></div>
                                        <div class="h-8 w-3/4 bg-purple-500/80 rounded"></div>
                                    </div>
                                </div>
                                <!-- Chart area -->
                                <div class="flex-1 rounded-xl bg-gray-800/30 border border-gray-700/50 p-4 flex items-end gap-2">
                                    @for ($i = 0; $i < 12; $i++)
                                        <div class="flex-1 bg-gradient-to-t from-indigo-500 to-purple-500 rounded-t-sm" style="height: {{ rand(20, 100) }}%"></div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Grid -->
            <section id="features" class="py-24 bg-gray-950/50 relative z-10 border-t border-gray-800/50">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-bold mb-4">Todo lo que necesitas en un solo lugar</h2>
                        <p class="text-gray-400 max-w-2xl mx-auto">Herramientas probadas para simplificar el día a día de tu negocio, permitiéndote enfocarte en lo que realmente importa: crecer.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Feature 1 -->
                        <div class="glassmorphism p-8 rounded-2xl hover:-translate-y-2 transition-transform duration-300 group">
                            <div class="w-14 h-14 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-6 group-hover:bg-emerald-500/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-white">Finanzas Claras</h3>
                            <p class="text-gray-400 leading-relaxed">Registra ingresos, gastos, cuentas por cobrar y por pagar. Entiende la salud financiera de tu empresa en tiempo real con reportes visuales.</p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="glassmorphism p-8 rounded-2xl hover:-translate-y-2 transition-transform duration-300 group">
                            <div class="w-14 h-14 rounded-xl bg-indigo-500/10 flex items-center justify-center mb-6 group-hover:bg-indigo-500/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-white">Inventario en Tiempo Real</h3>
                            <p class="text-gray-400 leading-relaxed">Controla tu stock al instante. Recibe alertas de bajo inventario, gestiona variantes de productos y optimiza tus compras sin esfuerzo.</p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="glassmorphism p-8 rounded-2xl hover:-translate-y-2 transition-transform duration-300 group">
                            <div class="w-14 h-14 rounded-xl bg-purple-500/10 flex items-center justify-center mb-6 group-hover:bg-purple-500/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-white">Métricas Accionables</h3>
                            <p class="text-gray-400 leading-relaxed">No más decisiones a ciegas. Paneles de control interactivos que muestran los productos más vendidos, márgenes de ganancia y tendencias.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-24 relative z-10">
                <div class="max-w-4xl mx-auto px-6 text-center">
                    <div class="glassmorphism rounded-3xl p-12 relative overflow-hidden border border-indigo-500/20">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/40 to-purple-900/40"></div>
                        <div class="relative z-10">
                            <h2 class="text-3xl md:text-5xl font-bold mb-6 text-white">¿Listo para transformar tu negocio?</h2>
                            <p class="text-xl text-indigo-200 mb-8 max-w-2xl mx-auto">Únete a cientos de emprendedores que ya están escalando sus operaciones con TiendaERP.</p>
                            <a href="{{ Route::has('register') ? route('register') : '#' }}" class="inline-block px-8 py-4 rounded-full bg-white text-gray-900 font-bold text-lg hover:bg-gray-100 transition-transform hover:scale-105 duration-200 shadow-xl">
                                Empieza tu prueba gratuita hoy
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="py-8 border-t border-gray-800/50 mt-auto relative z-10 bg-gray-950/80 backdrop-blur-md">
                <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-xs">
                            T
                        </div>
                        <span class="font-bold text-gray-300">TiendaERP</span>
                    </div>
                    <p class="text-sm text-gray-500">© {{ date('Y') }} TiendaERP. Todos los derechos reservados.</p>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-500 hover:text-white transition-colors">Términos</a>
                        <a href="#" class="text-gray-500 hover:text-white transition-colors">Privacidad</a>
                        <a href="#" class="text-gray-500 hover:text-white transition-colors">Contacto</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
