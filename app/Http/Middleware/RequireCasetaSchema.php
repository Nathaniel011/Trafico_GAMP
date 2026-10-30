<?php
namespace App\Http\Middleware;

use App\Services\SchemaCompatibility;
use Closure;

class RequireCasetaSchema
{
    public function handle($request, Closure $next)
    {
        if (!SchemaCompatibility::casetaControlAvailable()) {
            $message = 'El control de casetas requiere tablas que esta instalación no tiene. Puede consultar los documentos históricos y continuar con certificados y notificaciones.';
            if ($request->expectsJson()) return response()->json(['message' => $message], 503);
            return response()->view('casetas.unavailable', compact('message'), 503);
        }
        return $next($request);
    }
}
