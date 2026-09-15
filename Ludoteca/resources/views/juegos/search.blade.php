@extends('layouts.app')

@section('title', 'Búsqueda avanzada')

@section('content')
    <div class="mb-8">
        <p class="text-sm uppercase tracking-[0.25em] text-cyan-300">Catálogo</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Búsqueda avanzada</h1>
        <p class="mt-2 max-w-2xl text-slate-300">Encuentra juegos y expansiones por nombre, año de creación o idioma.</p>
    </div>

    <form action="{{ route('juegos.search') }}" method="GET" class="mb-10 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-xl shadow-slate-950/30">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label for="q" class="mb-2 block text-sm font-medium text-slate-200">Nombre del juego o expansión</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Ej. mar" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-cyan-400">
            </div>

            <div>
                <label for="anio_desde" class="mb-2 block text-sm font-medium text-slate-200">Año desde</label>
                <input id="anio_desde" name="anio_desde" type="number" min="1900" max="2100" value="{{ $filters['anio_desde'] ?? '' }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-slate-100 outline-none transition focus:border-cyan-400">
            </div>

            <div>
                <label for="anio_hasta" class="mb-2 block text-sm font-medium text-slate-200">Año hasta</label>
                <input id="anio_hasta" name="anio_hasta" type="number" min="1900" max="2100" value="{{ $filters['anio_hasta'] ?? '' }}" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-slate-100 outline-none transition focus:border-cyan-400">
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label for="idioma_id" class="mb-2 block text-sm font-medium text-slate-200">Idioma</label>
                <select id="idioma_id" name="idioma_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-slate-100 outline-none transition focus:border-cyan-400">
                    <option value="">Todos los idiomas</option>
                    @foreach($idiomas as $idioma)
                        <option value="{{ $idioma->id }}" @selected(($filters['idioma_id'] ?? '') == $idioma->id)>{{ $idioma->nombre }} ({{ $idioma->codigo }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 rounded-xl bg-cyan-500 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-400">Buscar</button>
                <a href="{{ route('juegos.search') }}" class="rounded-xl border border-slate-700 px-4 py-3 font-medium text-slate-200 transition hover:border-slate-500">Limpiar</a>
            </div>
        </div>

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif
    </form>

    <section class="mb-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-violet-300">Resultados</p>
                <h2 class="mt-1 text-2xl font-bold text-white">Juegos ({{ $juegos->count() }})</h2>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($juegos as $juego)
                <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/30">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full bg-violet-500/10 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.2em] text-violet-200">{{ $juego->anio }}</span>
                        <span class="text-xs text-slate-400">Juego #{{ $juego->id }}</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-bold text-white">{{ $juego->titulo }}</h3>
                    <p class="mt-2 text-sm text-slate-300">Idioma: <span class="font-medium text-cyan-300">{{ $juego->idioma->nombre }}</span></p>
                    <a href="{{ route('juegos.show', $juego) }}" class="mt-6 inline-flex rounded-xl bg-slate-800 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-700">Ver juego</a>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-700 p-8 text-slate-400 md:col-span-2 xl:col-span-3">No se encontraron juegos con esos criterios.</p>
            @endforelse
        </div>
    </section>

    <section>
        <div class="mb-4">
            <p class="text-sm uppercase tracking-[0.2em] text-fuchsia-300">Resultados relacionados</p>
            <h2 class="mt-1 text-2xl font-bold text-white">Expansiones ({{ $expansiones->count() }})</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($expansiones as $expansion)
                <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/30">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full bg-fuchsia-500/10 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.2em] text-fuchsia-200">{{ $expansion->idioma->nombre }}</span>
                        <span class="text-xs text-slate-400">Expansión #{{ $expansion->id }}</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-bold text-white">{{ $expansion->titulo }}</h3>
                    <p class="mt-2 text-sm text-slate-300">Juego base: <span class="font-medium text-cyan-300">{{ $expansion->juego->titulo }}</span></p>
                    <p class="mt-1 text-sm text-slate-400">Año: {{ $expansion->juego->anio }}</p>
                    <a href="{{ route('expansiones.show', $expansion) }}" class="mt-6 inline-flex rounded-xl bg-slate-800 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-700">Ver expansión</a>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-700 p-8 text-slate-400 md:col-span-2 xl:col-span-3">No se encontraron expansiones con esos criterios.</p>
            @endforelse
        </div>
    </section>
@endsection
