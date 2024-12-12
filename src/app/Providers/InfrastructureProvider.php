<?php

namespace App\Providers;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Customer\CustomerRepository;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\User\UserRepository;
use App\Domains\Visit\VisitRepository;
use App\Infrastructures\Authentication\EloquentAuthenticationRepository;
use App\Infrastructures\Authentication\Models\Authentication;
use App\Infrastructures\Cemetery\EloquentCemeteryRepository;
use App\Infrastructures\Customer\EloquentCustomerRepository;
use App\Infrastructures\DailyReport\EloquentDailyReportRepository;
use App\Infrastructures\Feedback\EloquentFeedbackRepository;
use App\Infrastructures\Schedule\EloquentScheduleRepository;
use App\Infrastructures\TransactionHistory\EloquentTransactionHistoryRepository;
use App\Infrastructures\User\EloquentUserRepository;
use App\Infrastructures\Visit\EloquentVisitRepository;
use Illuminate\Support\ServiceProvider;

class InfrastructureProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (
            [
                CemeteryRepository::class => EloquentCemeteryRepository::class,
                CustomerRepository::class => EloquentCustomerRepository::class,
                DailyReportRepository::class => EloquentDailyReportRepository::class,
                FeedbackRepository::class => EloquentFeedbackRepository::class,
                ScheduleRepository::class => EloquentScheduleRepository::class,
                TransactionHistoryRepository::class => EloquentTransactionHistoryRepository::class,
                UserRepository::class => EloquentUserRepository::class,
                VisitRepository::class => EloquentVisitRepository::class,
            ] as $interface => $implementation
        ) {
            $this->app->singleton($interface, $implementation);
        }

        $this->app->singleton(AuthenticationRepository::class, function (): AuthenticationRepository {
            return new EloquentAuthenticationRepository(
                new Authentication(),
                \config('sanctum.access_token_ttl'),
                \config('sanctum.refresh_token_ttl'),
                \config('sanctum.tokenable_type'),
                \config('sanctum.hash_salt')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
