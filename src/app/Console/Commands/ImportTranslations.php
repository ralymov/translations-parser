<?php

namespace Ralymov\TranslationsParser\Console\Commands;

use Ralymov\TranslationsParser\App\Services\GoogleSheetsImportService;
use Illuminate\Console\Command;

class ImportTranslations extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from file to google sheets API';

    /**
     * Execute the console command.
     */
    public function handle(): void {
        GoogleSheetsImportService::generateLanguageVersions();
    }
}
