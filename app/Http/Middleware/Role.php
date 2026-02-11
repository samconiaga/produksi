<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Role
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $userRole = strtolower(trim((string) ($user->role ?? '')));

        $allowed = array_values(array_filter(array_map(function ($r) {
            return strtolower(trim((string) $r));
        }, $roles)));

        if (!in_array($userRole, $allowed, true)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
