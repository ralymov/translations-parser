<?php

namespace Ralymov\TranslationsParser;

use Ralymov\TranslationsParser\Console\Commands\CreateSpreadsheet;
use Ralymov\TranslationsParser\Console\Commands\ExportTranslations;
use Ralymov\TranslationsParser\Console\Commands\ImportTranslations;
use Ralymov\TranslationsParser\Console\Commands\InitialParse;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {

        $this->publishes([
            __DIR__ . '/config/' => config_path()
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportTranslations::class,
                ImportTranslations::class,
                InitialParse::class,
                CreateSpreadsheet::class,
            ]);
        }

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
    }
}
