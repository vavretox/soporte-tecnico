<?php

namespace App\Http\Middleware;

use App\Services\CiudadaniaDigitalAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class HandleCiudadaniaDigitalCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasAny(['code', 'error']) && $request->has('state')) {
            Log::info('Ciudadania Digital callback received.', [
                'path' => $request->path(),
                'has_code' => $request->filled('code'),
                'has_error' => $request->filled('error'),
                'has_state' => $request->filled('state'),
                'has_nonce' => $request->filled('nonce'),
                'session_has_state' => $request->session()->has('ciudadania_digital.state'),
                'session_has_nonce' => $request->session()->has('ciudadania_digital.nonce'),
            ]);

            try {
                app(CiudadaniaDigitalAuth::class)->loginFromCallback($request);
            } catch (ValidationException $exception) {
                Log::warning('Ciudadania Digital callback failed in middleware.', [
                    'path' => $request->path(),
                    'errors' => $exception->errors(),
                ]);

                return redirect()->route('login')
                    ->withErrors($exception->errors())
                    ->with('ciudadania_digital_error', collect($exception->errors())->flatten()->first());
            }

            $user = $request->user();

            return $user?->isAgent()
                ? redirect()->route('admin.dashboard')->with('success', 'Ingreso con Ciudadania Digital correcto.')
                : redirect()->route('tickets.index')->with('success', 'Ingreso con Ciudadania Digital correcto.');
        }

        return $next($request);
    }
}
