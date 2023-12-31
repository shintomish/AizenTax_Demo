<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Actlog;
use \Route;
use Illuminate\Http\Request;

class ActlogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // return $next($request);
        $response = $next($request);
        $this->actlog($request, $response -> getStatusCode());
        return $response;
    }

    public function actlog($request, $status)
    {
        $user = $request -> user();
        $data = [
            'user_id' => $user ? $user->id : null,
            'route' => Route::currentRouteName(),
            'url' => $request -> path(),
            'method' => $request -> method(),
            'status' => $status,
            'data' => count($request->toArray()) != 0 ? json_encode($request->toArray()) : null,
            'remote_addr' => $request -> ip(),
            'user_agent' => $request -> userAgent(),
        ];
        Actlog::create($data);
    }
}
