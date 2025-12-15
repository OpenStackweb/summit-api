<?php namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class MemoryFootprintMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);

        $response = $next($request);

        $end = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $payload = [
            'method' => $request->method(),
            'path'   => $request->path(),
            'start_mb' => round($start / 1024 / 1024, 2),
            'end_mb'   => round($end / 1024 / 1024, 2),
            'delta_mb' => round(($end - $start) / 1024 / 1024, 2),
            'peak_mb'  => round($peak / 1024 / 1024, 2),
        ];

        Log::info('request_memory', $payload);

        // optional: expose in headers for local debugging
        $response->headers->set('X-Mem-Start-MB', (string)$payload['start_mb']);
        $response->headers->set('X-Mem-End-MB', (string)$payload['end_mb']);
        $response->headers->set('X-Mem-Peak-MB', (string)$payload['peak_mb']);

        return $response;
    }
}
