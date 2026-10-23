<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        abort_unless(config('features.'.$feature, false), 404);
        return $next($request);
    }
}
