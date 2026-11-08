<?php

namespace Eii\Captcha;

use Eii\Captcha\Console\CheckCaptchaCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eii-captcha.php', 'eii-captcha');

        $this->app->singleton(CaptchaManager::class, function ($app) {
            return new CaptchaManager($app['config']->get('eii-captcha', []));
        });

        $this->app->alias(CaptchaManager::class, 'eii-captcha');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/eii-captcha.php' => config_path('eii-captcha.php'),
        ], 'eii-captcha-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'eii-captcha');

        Blade::component('eii-captcha::components.widget', 'eii-captcha');

        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('eii-captcha-widget', \Eii\Captcha\Livewire\CaptchaWidget::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([CheckCaptchaCommand::class]);
        }
    }
}
