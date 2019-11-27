<?php

namespace KM\KMCrud;

use Illuminate\Support\ServiceProvider;
use KM\KMCrud\Console\KmCreateModuleCommand;

class KMCrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('KM\KMCrud\KMCrudController');
        $this->loadViewsFrom(__DIR__.'/views', 'km_crud');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/helpers.php';
        include __DIR__.'/routes.php';
        $this->publishes([
            __DIR__.'/assets/' => public_path('vendor/km/km_crud'),
        ], 'public');
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->commands([
            KmCreateModuleCommand ::class,
        ]);
    }
}
