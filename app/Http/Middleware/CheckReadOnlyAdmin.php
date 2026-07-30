<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReadOnlyAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isReadOnlyAdmin()) {
            // Permitir solo peticiones GET (lectura)
            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                // Para peticiones AJAX/API
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['message' => 'Acceso denegado. Perfil de Sólo Lectura.'], 403);
                }
                
                // Redirigir con error para peticiones web
                return redirect()->back()->with('error', 'Acceso denegado: Tu perfil es de Sólo Lectura.');
            }
        }

        return $next($request);
    }
}
