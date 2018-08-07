
# Introduction
This package contains command line utilities for database operations like backup, restore and create.

# Dependencies
The following packages are used as dependencies:
- Laravel Dotenv Editor (https://github.com/JackieDo/Laravel-Dotenv-Editor)
- Laravel Dropbox Driver (https://github.com/benjamincrozat/laravel-dropbox-driver)
- Flysystem Adapter for Google Drive (https://github.com/nao-pon/flysystem-google-drive)


# Configuration
- Publish the configuration using command `php artisan vendor:publish --provider='Vizrex\LaravelDbUtils\LaravelDbUtilsProvider'`
- This will publish 'dbutils.php' to `config/` 
- In `dbutils.php`, you can set default behavior for included command line utilities.
- Add the following sections in `disks` array in your `config/filesystems`


        'dropbox' => [
            'driver' => 'dropbox',
            'token' => env('DROPBOX_TOKEN'),
        ],
        
        'google' => [
            'driver' => 'google',
            'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
        ], 

    You also need to add these variables in .env with their values.
         
        DROPBOX_TOKEN=xxxxxxx

        GOOGLE_DRIVE_CLIENT_ID=xxxxxxxxxx
        GOOGLE_DRIVE_CLIENT_SECRET=xxxxxxxxxx
        GOOGLE_DRIVE_REFRESH_TOKEN=xxxxxxxxxx
        GOOGLE_DRIVE_FOLDER_ID=xxxxxxxxxx
- 
    This will be used to upload database backup file to dropbox and google drive.
- This gist is helpful for getting google drive credentials https://gist.github.com/sergomet/f234cc7a8351352170eb547cccd65011

# Console Commands

## db:backup

### Description
A console command to backup the application's default mysql database. the following config variables are used to backup the database:
- `database.connections.mysql.database`
- `database.connections.mysql.username`
- `database.connections.mysql.password`


### Signature
`db:backup 
        {--path= : The path to save the backup file.}
        {--ignore_tables= : Comma separated list of tables that should not be included in backup}
        {--compress= : Whether to compress the backup file. It accepts true or false}
        {--upload= : Whether to upload the backup file to Dropbox and Google Drive. It accepts true or false}                
        `
- If `--path` is not provided, then default path will be used. Default path can be configured in `config/dbutils.php` as `backup.path`
- If `--compress` is not provided, default value will be used which can be configured in `config/dbutils.php` as `backup.compress`
- If `--upload` is not provided, default value will be used which can be configured in `config/dbutils.php` as `backup.upload`


### Usage Examples
- `php artisan db:backup`
- `php artisan db:backup --path=/backups/ --compress=true`
- `php artisan db:backup --upload=true`
- `php artisan db:backup path= /backups/ --ignore_tables=cities,vehicles,invoices --upload=true --compress=true`

## db:restore
### Description
This command is used to resotre a database from given file. This command uses following config variables to determine which database to restore to.
- `database.connections.mysql.database`
- `database.connections.mysql.username`
- `database.connections.mysql.password`
- `database.connections.mysql.host`

### Signature
`db:restore {path}`

### Usage Example
`db:restore /backups/2018_08_03_12_12_42_726150_db_project.sql`

## db:create

### Description
This command is used to create a database for the application. It can also create a new user, give that user previllages for newly created database and set database variables in .env

### Signature
`db:create {new_database : name for the database to be created}`  
`{username : A username that has CREATE TABLE privileges}`  
`{--password= : Password for the user}`  
`{--host= : Database host.}`  
`{--port= : Database port.}`                          
`{--new_user= : If provided, a new user will be created and will be granted database privileges}`  
`{--new_password= : Password for the new user}`  
`{--set_env=true: Whether to write database variables to .env . It accepts true or false, default is true}`

#### Default configuration:
- `create` array in `config/dbutils.php` is used to set default values for host and port options.


### Usage Examples
- `php artisan db:create db_shop_mgmt --username=root --password=root`
- `php artisan db:create db_shop_mgmt --username=root --password=root --new_user=user123 --new_password=12345678`
- `php artisan db:create db_shop_mgmt --username=root --password=root --new_user=user123 --new_password=12345678 --set_env=false`

 ### Note:
 - Database charset will be utf8mb4 and collation will be set to utf8mb4_unicode_ci
 * if `--set_env` is true, the following variables in .env will be set:
    *   DB_DATABASE
    *   DB_HOST
    *   DB_PORT
    *   DB_USERNAME
    *   DB_PASSWORD

Backup of .env will be automatically stored at `storage/dotenv-editor/backups/` before new values are written.



