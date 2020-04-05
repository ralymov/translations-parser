<?php

namespace Ralymov\TranslationsParser\Console\Commands;

use Ralymov\TranslationsParser\App\Services\GoogleDriveService;
use Ralymov\TranslationsParser\App\Services\ParseService;
use Illuminate\Console\Command;

class ExportTranslations extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data from file to google sheets API';

    /**
     * Execute the console command.
     */
    public function handle(): void {
        ParseService::export();
    }
}
