<?php

namespace App\Providers;

use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            UserRepository::class
        );

        $this->app->singleton(
            CustomerRepository::class
        );

        $this->app->singleton(
            TransactionRepository::class
        );

        $this->app->singleton(
            WorkspaceRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
