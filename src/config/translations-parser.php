<?php

return [

    'original_filename' => [
        resource_path('/views/index.blade.php')
    ],
    'parsed_filename' => [
        resource_path('/views/index_parsed.blade.php')
    ],
    'google_sheets_filename' => 'translations',
    'spreadsheet_id' => '',
    'google_sheets_oauth_json' => base_path('client_secret.json'),
    'google_drive_oauth_json' => base_path('client_secret.json'),

];
