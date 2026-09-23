<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika tidak ada session token, kembalikan ke login
        if (!session()->has('jwt_token')) {
            return redirect()->route('login')->withErrors(['login_error' => 'Silakan login terlebih dahulu.']);
        }

        return $next($request);
    }
}
