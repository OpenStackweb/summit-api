<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class ServerTimingDoctrine
{
    public function handle($request, Closure $next): Response
    {
        $start = microtime(true);

        // Reset the request-scoped SQL timing accumulator. The actual per-query
        // timing is done by QueryTimingMiddleware, a DBAL Driver Middleware
        // registered globally in config/doctrine.php. That gives accurate
        // per-statement durations under DBAL 3.x prepared statements, which
        // the deprecated SQLLogger / Logging\Middleware paths do not.
        \App\Http\Middleware\Doctrine\QueryTimingCollector::reset();

        /** @var Response $response */
        $response = $next($request);

        $dbMs = \App\Http\Middleware\Doctrine\QueryTimingCollector::$totalMs;
        $dbCount = \App\Http\Middleware\Doctrine\QueryTimingCollector::$count;

        $end = microtime(true);
        $totalMs = ($end - $start) * 1000.0;
        $bootMs  = defined('LARAVEL_START') ? max(($start - LARAVEL_START) * 1000.0, 0.0) : 0.0;
        $appMs   = max($totalMs - $dbMs, 0.0);

        // Read controller-level timing markers (set by the controller via $request->attributes).
        // If the controller didn't set them, these phases are reported as 0.
        $attrs    = $request->attributes;
        $cStart   = $attrs->has("timing.controller_start")  ? (float) $attrs->get("timing.controller_start")  : null;
        $cEnd     = $attrs->has("timing.controller_end")    ? (float) $attrs->get("timing.controller_end")    : null;
        $sStart   = $attrs->has("timing.serializer_start")  ? (float) $attrs->get("timing.serializer_start")  : null;
        $sEnd     = $attrs->has("timing.serializer_end")    ? (float) $attrs->get("timing.serializer_end")    : null;

        $preMs        = ($cStart !== null) ? max(($cStart - $start) * 1000.0, 0.0) : 0.0;
        $controllerMs = ($cStart !== null && $cEnd !== null) ? max(($cEnd - $cStart) * 1000.0, 0.0) : 0.0;
        $serializerMs = ($sStart !== null && $sEnd !== null) ? max(($sEnd - $sStart) * 1000.0, 0.0) : 0.0;
        $postMs       = ($cEnd !== null) ? max(($end - $cEnd) * 1000.0, 0.0) : 0.0;

        $response->headers->set('Server-Timing',
            sprintf(
                'boot;dur=%.1f,pre;dur=%.1f,controller;dur=%.1f,db;dur=%.1f;desc="%d queries",serializer;dur=%.1f,post;dur=%.1f,app;dur=%.1f,total;dur=%.1f',
                $bootMs, $preMs, $controllerMs, $dbMs, $dbCount, $serializerMs, $postMs, $appMs, $totalMs
            )
        );
        $response->headers->set('Timing-Allow-Origin', '*');

        return $response;
    }
}
