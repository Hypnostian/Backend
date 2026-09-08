@extends('layouts.app')

@section('title', 'Editar idioma')

@section('content')
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-slate-950/50">
        <h1 class="mb-6 text-3xl font-bold text-white">Editar idioma</h1>

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('idiomas.update', $idioma->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="mb-2 block text-sm font-medium text-slate-200">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $idioma->nombre) }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none ring-0 transition focus:border-cyan-400">
            </div>

            <div>
                <label for="codigo" class="mb-2 block text-sm font-medium text-slate-200">Código</label>
                <input type="text" id="codigo" name="codigo" value="{{ old('codigo', $idioma->codigo) }}" maxlength="5" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none ring-0 transition focus:border-cyan-400">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-xl bg-cyan-500 px-5 py-3 font-semibold text-slate-950 transition hover:bg-cyan-400">Actualizar</button>
                <a href="{{ route('idiomas.index') }}" class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 font-medium text-slate-200 transition hover:border-slate-500">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
