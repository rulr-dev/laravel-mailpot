<?php

namespace Rulr\Mailpot;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use Rulr\Mailpot\Console\Commands\StatsCommand;
use Rulr\Mailpot\Console\Commands\CleanMailpotInbox;

class MailpotServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $manager) {
            $manager->extend('mailpot', function () {
                return new MailpotTransport();
            });
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanMailpotInbox::class,
                StatsCommand::class,
            ]);
        }
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        if (app()->environment('local')) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mailpot');

            Route::middleware('web')
                ->prefix('mailpot')
                ->group(__DIR__ . '/routes/web.php');
        }
    }
}
