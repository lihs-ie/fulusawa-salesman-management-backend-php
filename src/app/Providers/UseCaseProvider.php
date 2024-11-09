<?php

namespace App\Providers;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Customer\CustomerRepository;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\User\UserRepository;
use App\Domains\Visit\VisitRepository;
use App\UseCases\Authentication;
use App\UseCases\Cemetery;
use App\UseCases\Customer;
use App\UseCases\DailyReport;
use App\UseCases\Feedback;
use App\UseCases\Schedule;
use App\UseCases\User;
use App\UseCases\Visit;
use Illuminate\Support\ServiceProvider;

class UseCaseProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerAuthentication();
        $this->registerCemetery();
        $this->registerCustomer();
        $this->registerDailyReport();
        $this->registerFeedback();
        $this->registerSchedule();
        $this->registerUser();
        $this->registerVisit();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    private function registerAuthentication(): void
    {
        $this->app->singleton(Authentication::class, function ($app): Authentication {
            return new Authentication(
                $app->make(AuthenticationRepository::class)
            );
        });
    }

    private function registerCemetery(): void
    {
        $this->app->singleton(Cemetery::class, function ($app): Cemetery {
            return new Cemetery(
                $app->make(CemeteryRepository::class)
            );
        });
    }

    private function registerCustomer(): void
    {
        $this->app->singleton(Customer::class, function ($app): Customer {
            return new Customer(
                $app->make(CustomerRepository::class)
            );
        });
    }

    private function registerDailyReport(): void
    {
        $this->app->singleton(DailyReport::class, function ($app): DailyReport {
            return new DailyReport(
                $app->make(DailyReportRepository::class)
            );
        });
    }

    private function registerFeedback(): void
    {
        $this->app->singleton(Feedback::class, function ($app): Feedback {
            return new Feedback(
                $app->make(FeedbackRepository::class)
            );
        });
    }

    private function registerSchedule(): void
    {
        $this->app->singleton(Schedule::class, function ($app): Schedule {
            return new Schedule(
                $app->make(ScheduleRepository::class)
            );
        });
    }

    private function registerUser(): void
    {
        $this->app->singleton(User::class, function ($app): User {
            return new User(
                $app->make(UserRepository::class)
            );
        });
    }

    private function registerVisit(): void
    {
        $this->app->singleton(Visit::class, function ($app): Visit {
            return new Visit(
                $app->make(VisitRepository::class)
            );
        });
    }
}
