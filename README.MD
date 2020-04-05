# Setup guide

1. Add to **composer.json** file of your project section **repositories**:
```
"repositories": [  
  {  
    "type": "vcs",  
	"url": "git@gitlab.com:r.alymov/translations-parser.git"  
  },  
  {  
    "type": "vcs",  
	"url": "git@github.com:R-Alymov/php-html-parser.git"  
  }  
],
```
2. Add this library with **composer require icobox/translations-parser**.
3. Publish library config with **php artisan vendor publish --		provider=Icobox\TranslationsParser\ServiceProvider**
4. Edit published config **translations-parser.php** for your application (look at **Config** section of this Readme).
5. Generate OAuth2 tokens to use Google Api (look at **Tokens** section of this Readme) and edit path to json tokens in **translations-parse.php**.
6. Add class **translated** to markup tags with text that must be translated.
7. Use **php artisan translations:initial-parse** to parse markup.
8. Use **php artisan translation::export** to export parsed markup to Google Sheets table.
9. Use **php artisan translation::import** to import translations from Google Sheets table.


# Config

**translations-parse.php**:

 - **original_filename** - path to file with original markup (with **translatable** classes in tags that must be translatted).
 - **parsed_filename** - path to parsed file, that created with **php artisan translations:initial-parse** command.
 - **google_sheets_filename** - no comments.
 - **spreadsheet_id** - no comments.
 - **google_sheets_oauth_json** - path to OAuth2 json google sheets token file.
 - **google_drive_oauth_json** - path to OAuth2 json google drive token file.

# Tokens

1.  Use  [this link](https://console.developers.google.com/)  to create or select a project in the Google Developers Console and turn on the Google Sheets and Google Drive API.
2. Then **Go to credentials**.
3.  At the top of the page, select the  **OAuth consent screen**  tab. Select an  **Email address**, enter a  **Product name** if not already set, and click the  **Save**  button.
4.  Select the  **Credentials**  tab, click the  **Create credentials**  button and select  **OAuth client ID**.
5.  Select the application type  **Other**, enter some name, and click the  **Create** button.
6.  Click  **OK**  to dismiss the resulting dialog.
7.  Click the  file_download  (Download JSON) button to the right of the client ID.
8.  Move this file to your working directory and rename it  to **google_sheets_oauth_json** or **google_drive_oauth_json** from config file.

# WARNING
#### NESTED TAGS WITH TRANSLATED CLASSES NOT SUPPORTED YET.
