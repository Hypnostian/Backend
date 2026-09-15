@extends('layouts.app')

@section('title', 'Expansiones')

@section('content')
    <div class="mb-8 flex items-center justify-between gap-4">
        <div>
            <p class="text-sm uppercase tracking-[0.25em] text-cyan-300">Colección</p>
            <h1 class="mt-2 text-3xl font-bold text-white">Expansiones</h1>
        </div>
        <a href="{{ route('expansiones.create') }}" class="rounded-xl bg-cyan-500 px-4 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-400">+ Nueva expansión</a>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($expansiones as $expansion)
            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/40 transition hover:-translate-y-1 hover:border-cyan-400/40">
                <div class="flex items-center justify-between gap-3">
                    <span class="rounded-full bg-fuchsia-500/10 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.2em] text-fuchsia-200">{{ $expansion->idioma->nombre }}</span>
                    <span class="text-xs text-slate-400">#{{ $expansion->id }}</span>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-white">{{ $expansion->titulo }}</h2>
                <p class="mt-2 text-sm text-slate-300">Base: <span class="font-medium text-cyan-300">{{ $expansion->juego->titulo }}</span></p>

                <div class="mt-6 flex gap-3">
                    <a href="{{ route('expansiones.show', $expansion->id) }}" class="rounded-xl bg-slate-800 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-700">Ver</a>
                    <a href="{{ route('expansiones.edit', $expansion->id) }}" class="rounded-xl border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500">Editar</a>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-12 text-center text-slate-400 md:col-span-2 xl:col-span-3">
                No hay expansiones registradas.
            </div>
        @endforelse
    </div>
@endsection
