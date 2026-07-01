<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InstructorOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'instructor') {
            return response()->json([
                'message' => 'Only instructors can perform this action.'
            ], 403);
        }

        return $next($request);
    }
}