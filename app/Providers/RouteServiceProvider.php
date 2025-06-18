<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot()
    {
        $this->routes(function () {
           
            // ✅ REGISTER CLIENT ROUTE
            Route::middleware('api')
                ->prefix('api/v1/client')
                ->group(base_path('routes/client.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
