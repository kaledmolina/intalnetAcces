@extends('oauth.layout')

@section('title', 'Autorizar '.$client->name)

@section('content')
    <div class="card">
        <p class="eyebrow">Solicitud de acceso</p>
        <h1>{{ $client->name }} quiere conectarse a tu cuenta</h1>
        <p class="lead">
            Podrá consultar la asistencia de tu sede a través del conector MCP de IntalnetAcces.
            No puede modificar marcaciones, empleados, horarios ni notas.
        </p>

        <dl class="rows">
            <div>
                <dt>Tu cuenta</dt>
                <dd>{{ $user->name }}<br><span class="mono" style="font-weight:500;color:#64748b;">{{ $user->email }}</span></dd>
            </div>
            <div>
                <dt>Sede</dt>
                <dd>
                    @if($user->sedeRelation)
                        {{ $user->sedeRelation->name }} <span class="mono" style="font-weight:500;color:#64748b;">[{{ $user->sedeRelation->code }}]</span>
                    @elseif($user->sede)
                        {{ $user->sede }}
                    @else
                        Sin sede asignada
                    @endif
                </dd>
            </div>
            <div>
                <dt>Volverás a</dt>
                <dd class="mono">{{ $destination }}</dd>
            </div>
            @if($clientIdHost)
                <div>
                    <dt>Identidad declarada</dt>
                    <dd class="mono">{{ $clientIdHost }}</dd>
                </div>
            @endif
        </dl>

        <ul class="perms">
            <li>Leer marcaciones, tardanzas, horarios y horas trabajadas de tu sede.</li>
            <li>Generar reportes y exportaciones CSV de tu sede.</li>
            <li>Nada de escritura: el conector es de solo lectura.</li>
        </ul>

        @unless($user->sede_id || $user->sede)
            <p class="warn">
                Tu cuenta no tiene una sede asignada: el conector responderá con listas vacías hasta que un
                administrador te asigne una.
            </p>
        @endunless

        <form method="POST" action="{{ route('oauth.authorize') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client->client_id }}">
            <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
            <input type="hidden" name="response_type" value="code">
            <input type="hidden" name="code_challenge" value="{{ $codeChallenge }}">
            @if($state !== '')
                <input type="hidden" name="state" value="{{ $state }}">
            @endif

            <div class="actions">
                <button type="submit" name="decision" value="deny" class="btn ghost">Cancelar</button>
                <button type="submit" name="decision" value="approve" class="btn solid">Autorizar acceso</button>
            </div>
        </form>

        <p class="hint">
            Autoriza solo si tú iniciaste la conexión. Puedes revocar el acceso cuando quieras desde
            «Mi conector IA» → «Mis tokens». El token aparecerá con el nombre «{{ $client->name }} (OAuth)».
        </p>
    </div>
@endsection
