<?php

namespace Ralymov\TranslationsParser\App\Services;

use PHPHtmlParser\Dom;

class ParseService {

    public static function parse(): void {
        $originalFiles = config('translations-parser.original_filename');
        $parsedFiles = config('translations-parser.parsed_filename');
        foreach ($originalFiles as $index => $file) {
            if (file_exists($parsedFiles[$index])) {
                self::parseFile($parsedFiles[$index], $parsedFiles[$index]);
            } else {
                self::parseFile($file, $parsedFiles[$index]);
            }
        }
    }

    public static function parseFile($originalFileName, $parsedFileName): void {
        $dom = new Dom;
        $dom->loadFromFile($originalFileName, [
            'removeScripts' => false,
            'removeStyles' => false,
        ]);
        $contents = $dom->find('.translatable');
        $parsedData = [];

        foreach ($contents as $content) {
            if (!$content->getAttribute('data-id')) {
                $contentKey = array_search($content->text, $parsedData, true);
                if ($contentKey) {
                    $content->setAttribute('data-id', $contentKey);
                } else {
                    $content->setAttribute('data-id', self::generateRandomString());
                    $parsedData[$content->getAttribute('data-id')] = $content->text;
                }
            }
        }

        file_put_contents($parsedFileName, $dom);
    }

    public static function export(): void {
        GoogleSheetsExportService::export(self::prepareExportData());
    }

    public static function prepareExportData(): array {
        $parsedFiles = config('translations-parser.parsed_filename');
        $result = [];
        foreach ($parsedFiles as $file) {
            $result = array_merge($result, self::prepareExportDataFromFile($file));
        }
        return $result;
    }

    public static function prepareExportDataFromFile($fileName): array {
        $dom = new Dom;
        $dom->loadFromFile($fileName, [
            'removeScripts' => false,
            'removeStyles' => false,
        ]);
        $result = [];
        $contents = $dom->find('.translatable');
        for ($i = 0, $iMax = \count($contents); $i < $iMax; $i++) {
            if (!self::isKeyInArray($result, $contents[$i]->getAttribute('data-id'))) {
                $result[$i]['key'] = $contents[$i]->getAttribute('data-id');
                $result[$i]['value'] = $contents[$i]->text;
            }
        }
        return $result;
    }

    public static function isKeyInArray(array $array, string $key) {
        foreach ($array as $item) {
            if ($item['key'] === $key) return true;
        }
        return false;
    }

    public static function generateRandomString($length = 10): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}