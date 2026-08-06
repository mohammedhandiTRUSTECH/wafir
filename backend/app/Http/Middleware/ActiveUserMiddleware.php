<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveUserMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        if ($user and $user->is_active) {
            return $next($request);
        }
        auth('api')->logout();
        return response()->json(['status' => false,'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

    }
}
