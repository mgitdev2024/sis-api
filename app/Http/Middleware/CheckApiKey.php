<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $validKey = config('apikeys.sis_api_key');

        // Check if header is missing
        if (empty($apiKey)) {
            return response()->json(['message' => 'Unauthorized. Missing API Key.'], 401);
        }

        // Check if no key configured
        if (empty($validKey)) {
            return response()->json(['message' => 'Server misconfiguration. No API Key set.'], 500);
        }

        // Check if invalid
        if ($apiKey !== $validKey) {
            return response()->json(['message' => 'Unauthorized. Invalid API Key.'], 401);
        }

        return $next($request);
    }
}
