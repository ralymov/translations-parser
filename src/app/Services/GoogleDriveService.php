<?php

namespace Ralymov\TranslationsParser\App\Services;


use Exception;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use Google_Service_Exception;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_SpreadsheetProperties;
use Illuminate\Support\Facades\Config;

class GoogleDriveService {

    public static function createSpreadsheet() {
        $client = self::getClient();

        if (self::checkSpreadsheetExistence($client, config('translations-parser.spreadsheet_id'))) {
            echo 'Error: Spreadsheet already created' . PHP_EOL;
            die;
        }

        $spreadsheetProperties = new Google_Service_Sheets_SpreadsheetProperties();
        $spreadsheetProperties->setTitle(config('translations-parser.google_sheets_filename'));

        $newSpreadsheet = new Google_Service_Sheets_Spreadsheet();
        $newSpreadsheet->setProperties($spreadsheetProperties);

        $spreadsheetService = new Google_Service_Sheets($client);
        $response = $spreadsheetService->spreadsheets->create($newSpreadsheet);
        $spreadSheetId = $response->spreadsheetId;

        Config::set('translations-parser.spreadsheet_id', $spreadSheetId);
        self::setPermissions($client, $spreadSheetId);

        echo "\nId of your file - $spreadSheetId \nWrite it to translation-parser.php file if you don't have it already\n";
    }

    public static function setPermissions($client, $fileId) {
        $driveService = new Google_Service_Drive($client);
        $driveService->getClient()->setUseBatch(true);
        try {
            $batch = $driveService->createBatch();

            $userPermission = new Google_Service_Drive_Permission(array(
                'type' => 'anyone',
                'role' => 'writer',
            ));
            $request = $driveService->permissions->create(
                $fileId, $userPermission, array('fields' => 'id'));
            $batch->add($request, 'user');
            $results = $batch->execute();
        } finally {
            $driveService->getClient()->setUseBatch(false);
        }
    }

    public static function checkSpreadsheetExistence($client, $fileId) {
        if (!$fileId) return false;
        try {
            $driveService = new Google_Service_Drive($client);
            $spreadsheetFile = $driveService->files->get($fileId);

            if ($spreadsheetFile) {
                if ($spreadsheetFile->trashed) {
                    return false;
                }
                Config::set('translations-parser.google_sheets_filename', $spreadsheetFile->name);
                Config::set('translations-parser.spreadsheet_id', $spreadsheetFile->id);
                return true;
            }
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    }


    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     * @throws \Google_Exception
     */
    public static function getClient(): Google_Client {
        $client = new Google_Client();
        $client->setApplicationName('Google Drive API PHP Quickstart');
        $client->setScopes(Google_Service_Drive::DRIVE);
        $client->setAuthConfig(config('translations-parser.google_drive_oauth_json'));
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