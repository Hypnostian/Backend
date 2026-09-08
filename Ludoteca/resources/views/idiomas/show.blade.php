@extends('layouts.app')

@section('title', 'Detalle de idioma')

@section('content')
    <div class="mx-auto max-w-3xl rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-cyan-300">Idioma</p>
                <h1 class="mt-2 text-3xl font-bold text-white">{{ $idioma->nombre }}</h1>
            </div>
            <span class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3 py-1 text-sm font-medium text-cyan-200">{{ $idioma->codigo }}</span>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                <p class="text-sm text-slate-400">ID</p>
                <p class="mt-2 text-xl font-semibold text-white">#{{ $idioma->id }}</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                <p class="text-sm text-slate-400">Código</p>
                <p class="mt-2 text-xl font-semibold text-white">{{ $idioma->codigo }}</p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('idiomas.index') }}" class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:text-white">Volver</a>
            <a href="{{ route('idiomas.edit', $idioma->id) }}" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">Editar</a>
            <form action="{{ route('idiomas.destroy', $idioma->id) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este idioma?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-400">Eliminar</button>
            </form>
        </div>
    </div>
@endsection
