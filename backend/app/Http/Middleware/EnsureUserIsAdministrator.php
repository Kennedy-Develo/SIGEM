<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    /**
     * Allow access only to active administrators.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user === null
            || ! $user->isActive()
            || ! $user->isAdministrator()
        ) {
            abort(403, 'Você não possui permissão para acessar esta área.');
        }

        return $next($request);
    }
}
