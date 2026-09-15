<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GameBox Studio')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background:
                radial-gradient(circle at top, rgba(59,130,246,0.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(168,85,247,0.10), transparent 25%),
                #0f172a;
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <nav class="border-b border-slate-800/90 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-bold tracking-wide text-cyan-300">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 via-blue-500 to-violet-600 shadow-lg shadow-cyan-500/30">G</span>
                    GameBox Studio
                </a>

                <div class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-300">
                    <a href="{{ route('home') }}" class="transition hover:text-cyan-300">Inicio</a>
                    <a href="{{ route('idiomas.index') }}" class="transition hover:text-cyan-300">Idiomas</a>
                    <a href="{{ route('juegos.index') }}" class="transition hover:text-cyan-300">Juegos</a>
                    <a href="{{ route('expansiones.index') }}" class="transition hover:text-cyan-300">Expansiones</a>
                </div>

                <a href="{{ route('juegos.search') }}" class="inline-flex items-center rounded-full bg-cyan-500 px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/30 transition hover:bg-cyan-400">
                    Buscar
                </a>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 shadow-lg shadow-emerald-900/20">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
