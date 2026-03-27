<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.admin_token', '');

        if ($token === '') {
            abort(500, 'Admin token not configured.');
        }

        if ($request->session()->get('is_admin') === true) {
            return $next($request);
        }

        $providedToken = $request->query('admin');

        if (is_string($providedToken) && $providedToken !== '' && hash_equals($token, $providedToken)) {
            $request->session()->put('is_admin', true);

            return $next($request);
        }

        abort(403);
    }
}
