<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM-AC') — CRM-AC</title>

    {{-- Anti-flash: aplica dark antes de que cargue el CSS --}}
    <script>if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-slate-50 dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 antialiased min-h-screen transition-colors duration-200">

    @yield('content')

</body>
</html>
