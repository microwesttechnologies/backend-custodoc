<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Closure;

class LogRequest
{
/**
     * Manejar una solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('-----------------------------------------------------------');
        Log::info('Request URL: ' . $request->url());
        Log::info('Request Method: ' . $request->method());
        Log::info('Request Headers: ' . json_encode($request->headers->all()));
        Log::info('Request Body: ' . json_encode($request->all()));

        return $next($request); // Continuar con la solicitud
    }
}
