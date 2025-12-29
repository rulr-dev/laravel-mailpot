<?php

namespace Rulr\Mailpot;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Rulr\Mailpot\Console\Commands\CleanMailpotInbox;
use Rulr\Mailpot\Console\Commands\StatsCommand;

class MailpotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $manager) {
            $manager->extend('mailpot', function () {
                return new MailpotTransport;
            });
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanMailpotInbox::class,
                StatsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (app()->environment('local') === true) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailpot');

            Route::middleware('web')
                ->prefix('mailpot')
                ->group(__DIR__.'/routes/web.php');
        }
    }
}
