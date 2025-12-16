<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force la requête à accepter JSON
        $request->headers->set('Accept', 'application/json');

        // Si le Content-Type n'est pas défini ou n'est pas JSON, on l'accepte quand même
        // Laravel gérera automatiquement la conversion

        return $next($request);
    }
}
