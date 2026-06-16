<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render terminate TLS ที่ edge — เชื่อ X-Forwarded-Proto กัน asset/redirect หลุดเป็น http (Mixed Content)
        $middleware->trustProxies(at: '*');

        // guest แอดมิน → admin.login, ที่เหลือ → student.login
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin*')
            ? route('admin.login')
            : route('student.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
