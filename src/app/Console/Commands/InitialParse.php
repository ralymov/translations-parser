<?php

namespace Ralymov\TranslationsParser\Console\Commands;

use Ralymov\TranslationsParser\App\Services\ParseService;
use Illuminate\Console\Command;

class InitialParse extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:initial-parse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initial parse original HTML file, add to file data-id attributes';

    /**
     * Execute the console command.
     */
    public function handle(): void {
        ParseService::parse();
    }
}
