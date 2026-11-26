@extends('oauth.layout')

@section('title', 'No se pudo autorizar')

@section('content')
    <div class="card">
        <p class="eyebrow">No se pudo completar</p>
        <h1>Autorización no válida</h1>
        <p class="lead">{{ $message }}</p>
        <p class="hint">
            Vuelve a la aplicación (Claude o ChatGPT) e inicia la conexión otra vez. Si el problema
            continúa, avisa al administrador de IntalnetAcces.
        </p>
        <div class="actions" style="margin-top:18px;">
            <a class="btn solid" href="{{ url('/mi-mcp') }}">Ir a Mi conector IA</a>
        </div>
    </div>
@endsection
