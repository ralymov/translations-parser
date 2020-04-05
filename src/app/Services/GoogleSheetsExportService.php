<?php

namespace Ralymov\TranslationsParser\App\Services;

use Google_Client;
use Google_Service_Sheets;
use Ralymov\AdminPanel\Models\Admin\Locale;

class GoogleSheetsExportService {

    public static function export($data): void {
        $client = self::getClient();
        $service = new Google_Service_Sheets($client);
        $exportedData = self::getExportedData($service);
        $resultValues = self::prepareDataForNewKeys($exportedData, $data);

        self::update(
            $service,
            'A2:A' . (\count($resultValues) + 1),
            'COLUMNS',
            \array_column($resultValues, 'key')
        );
        self::update(
            $service,
            'B2:B' . (\count($resultValues) + 1),
            'COLUMNS',
            \array_column($resultValues, 'value')
        );
        self::update(
            $service,
            '!B1',
            'ROWS',
            Locale::sorted()->get()->pluck('name')
        );
        self::protect($service);
    }

    public static function prepareDataForNewKeys($exportedData, &$data): array {
        $resultValues = [];
        foreach ($exportedData as $key => $value) {
            $resultValues[] = [
                'key' => $key,
                'value' => html_entity_decode(self::popFromArray($data, $key)),
            ];
        }
        foreach ($data as $item) {
            $resultValues[] = [
                'key' => $item['key'],
                'value' => html_entity_decode($item['value']),
            ];
        }
        return $resultValues;
    }

    public static function getExportedData(Google_Service_Sheets $service): array {
        $translationsData = self::read($service, 'A2:B');
        $result = [];
        foreach ($translationsData as $data) {
            $result[$data[0]] = $data[1];
        }
        return $result;
    }

    public static function read(Google_Service_Sheets $service,
                                string $range = '') {
        return $service->spreadsheets_values->get(config('translations-parser.spreadsheet_id'), $range);
    }

    public static function update(Google_Service_Sheets $service,
                                  string $range = '',
                                  string $majorDimension = 'COLUMNS',
                                  $values): void {
        $updateBody = new \Google_Service_Sheets_ValueRange([
            'range' => $range,
            'majorDimension' => $majorDimension,
            'values' => ['values' => $values],
        ]);
        $service->spreadsheets_values->update(
            config('translations-parser.spreadsheet_id'),
            $range,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public static function popFromArray(&$array, $key) {
        foreach ($array as $index => $item) {
            if ($item['key'] === $key) {
                unset($array[$index]);
                return $item['value'];
            }
        }
        return null;
    }

    public static function protect(Google_Service_Sheets $service): void {
        $updateBody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $updateBody->setRequests([
            'addProtectedRange' => [
                'protectedRange' => [
                    'range' => [
                        'sheetId' => 0,
                        'startRowIndex' => 0,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 2,
                    ],
                    'description' => 'Protecting total row',
                    'editors' => [
                        'users' => [
                            'romanalym@gmail.com',
                        ]
                    ]
                ],
            ]
        ]);
        $service->spreadsheets->batchUpdate(
            config('translations-parser.spreadsheet_id'),
            $updateBody
        );
        $updateBody->setRequests([
            'addProtectedRange' => [
                'protectedRange' => [
                    'range' => [
                        'sheetId' => 0,
                        'startRowIndex' => 0,
                        'endRowIndex' => 1,
                        'startColumnIndex' => 0,
                    ],
                    'description' => 'Protecting total row',
                    'editors' => [
                        'users' => [
                            'romanalym@gmail.com',
                        ]
                    ]
                ]
            ]
        ]);
        $service->spreadsheets->batchUpdate(
            config('translations-parser.spreadsheet_id'),
            $updateBody
        );
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
        $credentialsPath = self::expandHomeDirectory('credentials.json');
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