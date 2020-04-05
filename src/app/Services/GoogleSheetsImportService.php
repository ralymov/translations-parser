<?php

namespace Ralymov\TranslationsParser\App\Services;

use Google_Client;
use Google_Service_Sheets;
use Ralymov\AdminPanel\Models\Admin\Locale;
use PHPHtmlParser\Dom;

class GoogleSheetsImportService {

    public static function generateLanguageVersions(): void {
        $originalFiles = config('translations-parser.original_filename');
        $parsedFiles = config('translations-parser.parsed_filename');
        foreach ($parsedFiles as $index => $file) {
            self::generateLanguageVersionsForFile($file, $originalFiles[$index]);
        }
    }

    public static function generateLanguageVersionsForFile($parsedFile, $fileName): void {
        $translations = self::importTranslations();
        foreach ($translations as $lang => $translation) {
            self::createLanguageHtml(
                $translation,
                $parsedFile,
                basename($fileName, '.blade.php') . "-$lang.blade.php"
            );
        }
    }

    /**
     * @param array $translations
     * @param string $parsedFile
     * @param string $fileName
     * @throws \RuntimeException
     */
    public static function createLanguageHtml(array $translations, string $parsedFile, string $fileName): void {
        if (!copy($parsedFile, resource_path("/views/$fileName"))) {
            throw new \RuntimeException("Can't generate language HTML file!");
        }

        $dom = new Dom;
        $dom->loadFromFile($parsedFile, [
            'removeScripts' => false,
            'removeStyles' => false,
        ]);
        $contents = $dom->find('.translatable');
        foreach ($contents as $content) {
            if ($content->getAttribute('data-id')) {
                if (isset($translations[$content->getAttribute('data-id')])) {
                    $content->setInnerHtml(
                        str_replace($content->text, $translations[$content->getAttribute('data-id')], strip_tags($content->innerHtml))
                    );
                }
            }
        }

        file_put_contents(resource_path("/views/$fileName"), $dom);
    }

    public static function importTranslations(): array {
        $client = self::getClient();
        $service = new Google_Service_Sheets($client);

        $translationsData = self::read($service, 'A2:Z');
        $locales = Locale::all();
        $translations = [];
        $i = 0;
        foreach ($locales as $locale) {
            foreach ($translationsData as $data) {
                $translations[$locale->code][$data[0]] = $data[$i + 1] ?? null;
            }
            $i++;
        }
        return $translations;
    }

    public static function read(Google_Service_Sheets $service,
                                string $range = '') {
        return $service->spreadsheets_values->get(config('translations-parser.spreadsheet_id'), $range);
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     * @throws \Google_Exception
     */
    public static function getClient(): Google_Client {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig(config('translations-parser.google_sheets_oauth_json'));
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        //$credentialsPath = self::expandHomeDirectory('credentials.json');
        $credentialsPath = base_path('credentials.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    public static function expandHomeDirectory($path): string {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }


}