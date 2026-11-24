<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class McpTokenController extends Controller
{
    public function index()
    {
        $users = User::with('sedeRelation')->orderBy('name')->get();

        $tokens = PersonalAccessToken::with('tokenable')
            ->where('abilities', 'like', '%"mcp:read"%')
            ->latest()
            ->get();

        $endpoint = url('/mcp/attendance');

        return view('mcp.index', compact('users', 'tokens', 'endpoint'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:60'],
        ], [
            'user_id.required' => 'Seleccione el usuario que usará el conector.',
            'user_id.exists' => 'El usuario seleccionado no existe.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (! $user->is_active) {
            return back()->withErrors([
                'user_id' => 'El usuario está inactivo; active su cuenta antes de generar un token.',
            ])->withInput();
        }

        $name = trim((string) ($validated['name'] ?? '')) ?: 'mcp-'.now()->format('Ymd-His');

        $token = $user->createToken($name, ['mcp:read']);

        return redirect()
            ->route('mcp.index')
            ->with('success', "Token generado para {$user->name}.")
            ->with('mcp_plain_text_token', $token->plainTextToken)
            ->with('mcp_token_user', $user->name);
    }

    /**
     * Vista de autoservicio: el usuario ve y gestiona únicamente sus tokens.
     */
    public function selfIndex()
    {
        $user = Auth::user()->load('sedeRelation');

        $tokens = $user->tokens()
            ->where('abilities', 'like', '%"mcp:read"%')
            ->latest()
            ->get();

        $endpoint = url('/mcp/attendance');

        return view('mcp.self', compact('user', 'tokens', 'endpoint'));
    }

    /**
     * Genera un token siempre para el usuario autenticado (ignora cualquier user_id enviado).
     */
    public function selfStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user()->fresh() ?? $request->user();

        if (! $user->is_active) {
            return back()->with('error', 'Tu cuenta está inactiva; no puedes generar tokens MCP.');
        }

        $name = trim((string) ($validated['name'] ?? '')) ?: 'mcp-'.now()->format('Ymd-His');

        $token = $user->createToken($name, ['mcp:read']);

        return redirect()
            ->route('mcp.self')
            ->with('success', 'Token generado correctamente.')
            ->with('mcp_plain_text_token', $token->plainTextToken)
            ->with('mcp_token_user', $user->name);
    }

    /**
     * Revoca un token propio; un token de otro usuario responde 404.
     */
    public function selfDestroy(Request $request, PersonalAccessToken $token)
    {
        $user = $request->user();

        $isOwner = $token->tokenable_type === $user->getMorphClass()
            && (int) $token->tokenable_id === (int) $user->id;

        if (! $isOwner || ! in_array('mcp:read', $token->abilities ?? [], true)) {
            abort(404);
        }

        $name = $token->name;
        $token->delete();

        return back()->with('success', "El token '{$name}' fue revocado.");
    }

    public function destroy(PersonalAccessToken $token)
    {
        if (! in_array('mcp:read', $token->abilities ?? [], true)) {
            abort(404);
        }

        $name = $token->name;
        $token->delete();

        return back()->with('success', "El token '{$name}' fue revocado.");
    }
}
