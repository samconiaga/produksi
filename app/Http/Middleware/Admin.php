<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(401);
        }

        $role = strtolower(trim((string) (auth()->user()->role ?? '')));
        $admins = ['admin', 'administrator', 'superadmin'];

        if (!in_array($role, $admins, true)) {
            abort(403, 'Admin only.');
        }

        return $next($request);
    }
}
