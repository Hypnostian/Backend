@extends('layouts.app')

@section('title', 'Editar expansión')

@section('content')
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50">
        <h1 class="mb-6 text-3xl font-bold text-white">Editar expansión</h1>

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('expansiones.update', $expansion->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="titulo" class="mb-2 block text-sm font-medium text-slate-200">Título</label>
                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $expansion->titulo) }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none transition focus:border-cyan-400">
            </div>

            <div>
                <label for="juego_id" class="mb-2 block text-sm font-medium text-slate-200">Juego base</label>
                <select id="juego_id" name="juego_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none transition focus:border-cyan-400">
                    @foreach($juegos as $juego)
                        <option value="{{ $juego->id }}" {{ old('juego_id', $expansion->juego_id) == $juego->id ? 'selected' : '' }}>{{ $juego->titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="idioma_id" class="mb-2 block text-sm font-medium text-slate-200">Idioma</label>
                <select id="idioma_id" name="idioma_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none transition focus:border-cyan-400">
                    @foreach($idiomas as $idioma)
                        <option value="{{ $idioma->id }}" {{ old('idioma_id', $expansion->idioma_id) == $idioma->id ? 'selected' : '' }}>{{ $idioma->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-xl bg-cyan-500 px-5 py-3 font-semibold text-slate-950 transition hover:bg-cyan-400">Actualizar</button>
                <a href="{{ route('expansiones.show', $expansion->id) }}" class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 font-medium text-slate-200 transition hover:border-slate-500">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
