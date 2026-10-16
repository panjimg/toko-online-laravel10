<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Models\Kategori; // Import model Kategori Anda

class AppServiceProvider extends ServiceProvider
    {
    
        /**
         * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan variabel $kategori khusus untuk file view v_layouts.app
        View::composer('v_layouts.app', function ($view) {
            $view->with('kategori', Kategori::all());
        });
        Paginator::useBootstrap();
    }
}
