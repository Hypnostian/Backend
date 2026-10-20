@extends('emails.layout')

@section('contenido')
    <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;color:#0f1b2d;">{{ $asunto }}</h1>
    <div style="font-size:15px;line-height:1.6;color:#22324a;">{!! nl2br(e($mensaje)) !!}</div>
@endsection
