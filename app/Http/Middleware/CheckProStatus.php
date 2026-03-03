<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isPro()) {
            return response()->json([
                'message' => 'Fitur ini hanya tersedia untuk pengguna Pro.',
                'upgrade_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
