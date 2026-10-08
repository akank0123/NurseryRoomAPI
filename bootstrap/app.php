<?php

use App\Exceptions\AlreadyCheckedOutException;
use App\Exceptions\ChildAlreadyCheckedInException;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [IdentifyTenant::class]);

        // The tenant must be known before {room} / {checkIn} are resolved,
        // so that route model binding goes through the tenant scope and a
        // record from another tenant is simply "not found".
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: IdentifyTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // Expected business outcomes, not errors worth logging.
        $exceptions->dontReport([
            ChildAlreadyCheckedInException::class,
            AlreadyCheckedOutException::class,
        ]);

        // Same response whether the record never existed or belongs to
        // another tenant, and no internal model names in the message.
        $exceptions->render(fn (NotFoundHttpException $e) => response()->json(
            ['message' => 'Not found.'],
            404,
        ));
    })->create();
