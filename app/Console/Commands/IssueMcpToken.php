<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueMcpToken extends Command
{
    protected $signature = 'mcp:token {email : Correo del usuario que usará el cliente MCP} {--name=freebuff : Nombre identificable del token}';

    protected $description = 'Genera un token Sanctum de solo lectura (mcp:read) para el servidor MCP y lo muestra una única vez.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No existe un usuario con ese correo.');

            return self::FAILURE;
        }

        if (! $user->is_active) {
            $this->error('El usuario está inactivo; el token no se generará.');

            return self::FAILURE;
        }

        $token = $user->createToken((string) $this->option('name'), ['mcp:read']);

        $this->newLine();
        $this->warn('Guarda este token ahora: no se volverá a mostrar.');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line('Usuario: '.$user->email);
        $this->line('Sede: '.($user->sedeRelation?->code ?? $user->sede ?? 'sin sede'));

        return self::SUCCESS;
    }
}
