<?php

namespace App\Providers;

use App\Interfaces\AuthInterface;
use App\Interfaces\CategoryInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Repositories\AuthRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthInterface::class,     AuthRepository::class);
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(OrderInterface::class,    OrderRepository::class);
        $this->app->bind(ProductInterface::class,  ProductRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
