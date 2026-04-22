<?php

namespace App\Providers;

use App\Models\SubscriptionLog;
use App\Observers\SubscriptionLogObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // On Vercel (serverless), override storage path to /tmp
        // since the default storage/ directory is read-only
        if (isset($_ENV['APP_STORAGE'])) {
            $this->app->useStoragePath($_ENV['APP_STORAGE']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');

            if (request()->server->has('HTTP_X_FORWARDED_PROTO')) {
                request()->server->set('HTTPS', 'on');
            }
        }

        SubscriptionLog::observe(SubscriptionLogObserver::class);
    }
}
