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

        // Capture all log messages to database (works with any LOG_CHANNEL,
        // including Laravel Cloud's laravel-cloud-socket)
        \Illuminate\Support\Facades\Log::listen(function (\Illuminate\Log\Events\MessageLogged $event) {
            try {
                \App\Models\AppLog::create([
                    'level'   => $event->level,
                    'channel' => 'app',
                    'message' => $event->message,
                    'context' => !empty($event->context) ? $event->context : null,
                ]);
            } catch (\Throwable $e) {
                // Silently fail — logging should never break the app
            }
        });
    }
}
