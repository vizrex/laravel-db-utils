<?php

namespace Vizrex\LaravelDbUtils\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades;
use DateTime;
use Config;
use DB;
use Storage;
use Srmklive\Dropbox\Client\DropboxClient;
use Srmklive\Dropbox\Adapter\DropboxAdapter;
use Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter;
use App\Providers\GoogleDriveServiceProvider;
use League\Flysystem\Filesystem;

use Vizrex\Laraviz\Console\Commands\BaseCommand;

class BackupDatabase extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
            {--path= : The path to save the backup file.}
            {--ignore_tables= : Comma separated list of tables that should not be included in backup}
            {--compress= : Whether to compress the backup file. It accepts true or false}
            {--upload= : Whether to upload the backup file to Dropbox and Google Drive. It accepts true or false}                
            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commands takes backup of the database specified in .env';

    private $path;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();        
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {             
        $ignoredTablesParam = $this->option('ignore_tables');
        $compressParam = strtolower($this->option('compress'));
        $uploadParam = strtolower($this->option('upload'));             
        
        $ignoredTables = [];
        $ignoredTablesString = '';
        $compress = '';   
        $uploadToCloud = '';
        $compressedFile = '';
        
        if(empty($compressParam))
        {
            $compress = config('dbutils.backup.compress');
        }
        else
        {
            if($compressParam != 'true' && $compressParam != 'false')
            {
                throw new Exception($this->str("invalid_option_value", ['target' => '--compress']));
            }
            else
            {
                $compress = $compressParam;
            }
        }
        
        if(empty($uploadParam))
        {
            $uploadToCloud = config('dbutils.backup.upload');
        }
        else
        {
            if($uploadParam != 'true' && $uploadParam != 'false')
            {
                throw new Exception($this->str("invalid_option_value", ['target' => '--upload']));
            }
            else
            {
                $uploadToCloud = $uploadParam;
            }
        }
        
        $pathParam = $this->option('path');
        //setting path:             
        if(!empty($pathParam))
        {                       
            if (file_exists($pathParam))
            {
                $this->path = $pathParam;                
            }
            else
            {
                throw new Exception($this->str('path_not_found', ['target' => $pathParam]));
            }
        }
        else
        {
            $defaultPath = config('dbutils.backup.path');
            if (!file_exists($defaultPath))
            {
                mkdir($defaultPath, 0775, true);
                $this->info($this->str("directory_created", ['target' => $defaultPath]));
            }
            $this->path = $defaultPath;
        }

        //setting username and password
        $dbName = Config::get("database.connections.mysql.database");
        $dbUser = Config::get("database.connections.mysql.username");
        $dbPassword = Config::get("database.connections.mysql.password");
        
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
        $microSeconds = $d->format("u"); 
        
        $file = date('Y')."_".date('m')."_".date('d')."_".date('H')."_".date('i')."_".date('s') . "_".$microSeconds."_" . $dbName . ".sql";        
        
        if(!empty($ignoredTablesParam))
        {
            $ignoredTables = explode(',', $ignoredTablesParam);
            $ignoredTablesString = self::getIgnoredTablesString($dbName, $ignoredTables);
        }
        $this->error($ignoredTablesString);
        
        //backing up the database
        $this->info("Backing up the database...");
        $command = sprintf('mysqldump %s -u %s -p\'%s\' %s --single-transaction --no-create-db --disable-keys > %s', $dbName, $dbUser, $dbPassword, $ignoredTablesString, $this->path . $file);
        DB::statement("SET foreign_key_checks = 0");
        exec($command, $output, $return);
        DB::statement("SET foreign_key_checks = 1");
        if(!$return)
        {
            $this->info($this->str("db_backup_stored_at", ['target' => $this->path.$file]));            
        }
        else
        {
            throw new Exception($this->str("db_backup_failed"));
        }
        
        if($compress == 'true')
        {
            $compressedFile = self::compressBackupFile($this->path , $file);       
        }       
            
        if($uploadToCloud == 'true')
        {
            if(!empty($compressedFile))
            {
                self::uploadBackupFileToCloud($this->path, $compressedFile);
            }
            else
            {
                self::uploadBackupFileToCloud($this->path, $file);
            }
            
        }
    }
    
    private function getIgnoredTablesString($dbName, $ignoredTables)
    {
        $ignoredTablesString = '';
        foreach($ignoredTables as $table)
        {
            $ignoredTablesString = ' '. $ignoredTablesString . '' . '--ignore-table=' . $dbName . '.' . $table . ' ';
        }
        
        return $ignoredTablesString;
    }
    
    private function compressBackupFile($path, $file)
    {
        $this->info($this->str("compressing_backup_file"));
        
        $compressedFileName = $file . '.tar.xz';
        
        $compressCommand = sprintf('cd %s && tar -cJf %s %s', $path, $compressedFileName, $file);
        self::runShellCommand($compressCommand, "Compressed backup file successfully stored at:", "An ERROR occured while compressing the database file.", $path.$compressedFileName);
        
        //delete original file
        $this->info($this->str("deleting_original_file"));
        
        $compressedFileName = $file . '.tar.xz';
        
        $deleteCommand = sprintf('rm -f %s', $path.$file);
        self::runShellCommand($deleteCommand, 'Done', 'An ERROR occured while deleting the original file.');        
        
        return $compressedFileName;
    }
    
    private function runShellCommand($command, $successMsg, $failureMsg, $refrence = '')
    {
        exec($command, $output, $return);
        if(!$return)
        {
            $this->info($successMsg . ' ' . $refrence);            
        }
        else
        {
            throw new Exception($failureMsg . ' ' . $refrence);
        } 
    }
    
    private function uploadBackupFileToCloud($path, $file)
    {
        $fileContent = file_get_contents($path.$file);
        
        $this->info($this->str("uploading_to_dropbox"));
        Storage::disk('dropbox')->put($file, $fileContent);  
        $this->info($this->str("done_uploading"));
        
        $this->info($this->str("uploading_to_google_drive"));
        Storage::disk('google')->put($file, $fileContent);        
        $this->info($this->str("done_uploading"));
    }
    
    protected function setNamespace()
    {
        $this->namespace = \Vizrex\LaravelDbUtils\LaravelDbUtilsProvider::getNamespace();
    }
}
