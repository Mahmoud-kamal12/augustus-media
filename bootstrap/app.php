<?php

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware()
    ->withExceptions([ApiExceptionRenderer::class, 'register'])
    ->create();
