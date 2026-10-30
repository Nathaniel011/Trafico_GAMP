<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->addToMiddlewarePriorityBefore(
            \Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\RequireCasetaSchema::class
        );
        \Illuminate\Session\Middleware\AuthenticateSession::redirectUsing(fn ($request) => $request->expectsJson() ? null : route('login'));
    }
}
