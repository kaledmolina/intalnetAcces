<?php

namespace App\Mcp\Tools\Concerns;

use App\Mcp\Support\AttendanceQueries;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class ReadOnlyAttendanceTool extends Tool
{
    protected function queries(): AttendanceQueries
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Se requiere un token válido.');
        }

        $token = $user->currentAccessToken();

        if ($token !== null && ! $user->tokenCan('mcp:read')) {
            throw new AuthorizationException('El token no tiene el permiso mcp:read.');
        }

        return new AttendanceQueries($user);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function respond(array $payload): ResponseFactory
    {
        Log::info('mcp.attendance.tool_called', [
            'tool' => $this->name(),
            'user_id' => auth()->id(),
            'rows' => count($payload['data'] ?? []),
            'total' => $payload['total'] ?? null,
            'truncated' => $payload['truncated'] ?? false,
        ]);

        return Response::structured([
            'herramienta' => $this->name(),
            ...$payload,
        ]);
    }
}
