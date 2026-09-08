@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <section class="rounded-[2rem] border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50">
        <div class="grid gap-10 lg:grid-cols-[1.5fr_1fr] lg:items-center">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-cyan-300">Dashboard</p>
                <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">Tu catálogo de juegos, idiomas y expansiones</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-300">
                    Gestiona el contenido de tu biblioteca de videojuegos con una experiencia moderna, clara y totalmente enfocada a la narrativa del proyecto.
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('idiomas.index') }}" class="rounded-xl bg-cyan-500 px-5 py-3 font-semibold text-slate-950 transition hover:bg-cyan-400">Ver idiomas</a>
                    <a href="{{ route('juegos.index') }}" class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 font-semibold text-slate-100 transition hover:border-slate-500">Ver juegos</a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                <div class="rounded-2xl border border-cyan-500/30 bg-cyan-500/10 p-5">
                    <p class="text-sm text-cyan-200">Idiomas</p>
                    <p class="mt-3 text-3xl font-bold text-white">{{ App\Models\Idioma::all()->count() }}</p>
                </div>
                <div class="rounded-2xl border border-violet-500/30 bg-violet-500/10 p-5">
                    <p class="text-sm text-violet-200">Juegos</p>
                    <p class="mt-3 text-3xl font-bold text-white">{{ App\Models\Juego::all()->count() }}</p>
                </div>
                <div class="rounded-2xl border border-fuchsia-500/30 bg-fuchsia-500/10 p-5">
                    <p class="text-sm text-fuchsia-200">Expansiones</p>
                    <p class="mt-3 text-3xl font-bold text-white">{{ App\Models\Expansion::all()->count() }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
