<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->rol_corp) {
            return response()->json(['message' => 'Error'], 403);
        }
        return $next($request);
    }
}
