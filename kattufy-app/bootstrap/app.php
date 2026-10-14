<?php

use App\Http\Middleware\EnsureArea;
use App\Http\Middleware\EnsureProviderApproved;
use App\Http\Middleware\EnsureRoleNotChosen;
use App\Http\Middleware\RequireMember;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrailingSlash;
use App\Modules\Accounts\Services\AccountHome;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'member' => RequireMember::class,
            'area' => EnsureArea::class,
            'provider.approved' => EnsureProviderApproved::class,
            'role.unchosen' => EnsureRoleNotChosen::class,
        ]);

        $middleware->web(append: [TrailingSlash::class, SecurityHeaders::class]);

        $middleware->redirectGuestsTo(fn (Request $request) => lroute('login'));
        $middleware->redirectUsersTo(fn (Request $request) => AccountHome::url($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
