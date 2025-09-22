<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtlpDemoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        Log::debug("test-otpl route called");
        return response()->json(['traceid' => \Keepsuit\LaravelOpenTelemetry\Facades\Tracer::traceId()]);
    }

}