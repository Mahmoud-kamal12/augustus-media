<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiTokenIfPresent
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->auth->guard('sanctum')->user();

        if ($user) {
            $this->auth->shouldUse('sanctum');
        }

        return $next($request);
    }
}
