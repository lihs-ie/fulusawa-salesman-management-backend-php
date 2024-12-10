<?php

use App\Domains\User\ValueObjects\Role;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\CemeteryController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\DailyReportController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\HealthCheckController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\TransactionHistoryController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VisitController;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

Route::controller(HealthCheckController::class)->group(function (): void {
    Route::get('/health-check', 'check');
});

$sanctum = fn (Role ...$roles): RouteRegistrar => Route::middleware(
    ['auth:sanctum', \sprintf('role:%s', \implode(',', Collection::make($roles)->map->name->all()))]
);

Route::prefix('auth')
    ->controller(AuthenticationController::class)
    ->group(function () use (&$sanctum): void {
        Route::post('login', 'login');

        $sanctum(Role::ADMIN, Role::USER)
            ->group(function (): void {
                Route::post('logout', 'logout');
                Route::post('introspect', 'introspect');
                Route::post('revoke', 'revoke');
                Route::post('token', 'refresh');
            })
        ;
    })
;

Route::prefix('cemeteries')
    ->controller(CemeteryController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::ADMIN, Role::USER)
            ->group(function (): void {
                Route::get('', 'list');
                Route::post('', 'add');

                Route::prefix('{identifier}')
                    ->group(function (): void {
                        Route::get('', 'find');
                        Route::put('', 'update');
                    })
                ;
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::delete('{identifier}', 'delete');
            })
        ;
    })
;

Route::prefix('customers')
    ->controller(CustomerController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::ADMIN, Role::USER)
            ->group(function (): void {
                Route::get('', 'list');
                Route::post('', 'add');

                Route::prefix('{identifier}')
                    ->group(function (): void {
                        Route::get('', 'find');
                        Route::put('', 'update');
                    })
                ;
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::delete('{identifier}', 'delete');
            })
        ;
    })
;

Route::prefix('daily-reports')
    ->controller(DailyReportController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::get('{identifier}', 'find');
            })
        ;

        $sanctum(Role::USER)
            ->group(function (): void {
                Route::post('', 'add');
                Route::put('{identifier}', 'update');
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::delete('{identifier}', 'delete');
            })
        ;
    })
;

Route::prefix('feedbacks')
    ->controller(FeedbackController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::get('{identifier}', 'find');
            })
        ;

        $sanctum(Role::USER)
            ->group(function (): void {
                Route::post('', 'add');
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::put('{identifier}', 'update');
            })
        ;
    })
;

Route::prefix('schedules')
    ->controller(ScheduleController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::post('', 'add');

                Route::prefix('{identifier}')
                    ->group(function (): void {
                        Route::get('', 'find');
                        Route::put('', 'update');
                        Route::delete('', 'delete');
                    })
                ;
            })
        ;
    })
;

Route::prefix('transaction-histories')
    ->controller(TransactionHistoryController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::post('', 'add');
                Route::get('{identifier}', 'find');
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::put('{identifier}', 'update');
                Route::delete('{identifier}', 'delete');
            })
        ;
    })
;

Route::prefix('users')
    ->controller(UserController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::post('', 'add');
                Route::get('{identifier}', 'find');
            })
        ;

        $sanctum(Role::ADMIN)
            ->group(function (): void {
                Route::put('{identifier}', 'update');
                Route::delete('{identifier}', 'delete');
            })
        ;
    })
;

Route::prefix('visits')
    ->controller(VisitController::class)
    ->group(function () use (&$sanctum): void {
        $sanctum(Role::USER)
            ->group(function (): void {
                Route::post('', 'add');
                Route::put('{identifier}', 'update');
                Route::delete('{identifier}', 'delete');
            })
        ;

        $sanctum(Role::USER, Role::ADMIN)
            ->group(function (): void {
                Route::get('', 'list');
                Route::get('{identifier}', 'find');
            });
    });
