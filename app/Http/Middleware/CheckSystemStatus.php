<?php

namespace App\Http\Middleware;

use App\Models\Admin\System\ScmSystemModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $systemName): Response
    {
        $system = ScmSystemModel::where('code', $systemName)->first();

        if ($system && in_array($system->status, [2, 3])) {
            $statusMessage = [
                1 => 'Running',
                2 => 'Down',
                3 => 'Maintenance'
            ];
            $data = [
                'http_code' => 503,
                'code' => $system->code,
                'name' => $system->name,
                'status_id' => $system->status,
                'status_message' => $statusMessage[$system->status],
            ];

            $message = [
                'message' => "The {$system->name} is currently down or under maintenance.",
                'data' => $data
            ];
            $errorMessage = [
                'error' => $message
            ];
            return response()->json($errorMessage, 503); // HTTP 503 Service Unavailable
        }

        return $next($request);
    }
}
