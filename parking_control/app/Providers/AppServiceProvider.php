<?php

namespace App\Providers;

use App\Repository\ParkingRepository;
use Illuminate\Support\ServiceProvider;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Repository\Eloquent\UserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
        UserRepositoryInterface::class,
        UserRepository::class,    
       
    );
    $this->app->bind(
       // ParkingRepositoryInterface::class,
        ParkingRepository::class,     
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        if (config('app.env') === 'production' || env('APP_URL') !== 'http://localhost') {
            URL::forceScheme('https');
        }
    }
}