<?php

namespace Vizrex\LaravelDbUtils\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use DB;
use Illuminate\Support\Facades;
use Config;

use Vizrex\Laraviz\Console\Commands\BaseCommand;

class RestoreDatabase extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command restores the database at the given path to the database defined in .env file.';

    private $dbName = null;
    private $dbUser = null;
    private $dbPassword = null;
    private $host = null;
    private $path = null;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        //setting username and password
        $this->dbName = Config::get("database.connections.mysql.database");
        $this->dbUser = Config::get("database.connections.mysql.username");
        $this->dbPassword = Config::get("database.connections.mysql.password");
        $this->host = Config::get('database.connections.mysql.host'); 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {       
        // $this->info('name: '.$this->dbName.' User: '.$this->dbUser.' Password: '.$this->dbPassword.' host: '.$this->host);               
        $param = $this->argument('path');
        if (file_exists($param))
        {
            $this->path = $param;                
        }
        else
        {            
            throw new Exception( "Path Not Found!" );
        }        

        if(!empty($this->path))
        {    
            $this->info('Connecting to Database ' . $this->dbName);  
            try 
            {
                DB::connection()->getPdo();                
            } 
            catch (\Exception $e) 
            {
                throw new Exception("Could not connect to the database.");                
            }                             

            $tables = DB::select('SHOW TABLES');                
            if(!empty($tables))
            {
                $input = $this->ask("Warning: database has one or more tables, do you want to drop all existing tables before restoring new database? yes/no ");
                if($input == 'yes')
                {
                    DB::statement("SET foreign_key_checks = 0");
                    foreach($tables as $table) 
                    {                                                                                                                   
                        $table_array = get_object_vars($table);
                        \Schema::drop($table_array[key($table_array)]);
                    }
                    DB::statement("SET foreign_key_checks = 1");
                    $this->info("All existing tables dropped."); 
                    $this->restoreDb($this->dbUser, $this->dbPassword, $this->host, $this->dbName, $this->path);                       
                }
                else
                {                    
                    $this->error("No tables were dropped, restore aborted.");                                                
                }
            }
            else
            {
                // $this->info('No existing tables found.');
                $this->restoreDb($this->dbUser, $this->dbPassword, $this->host, $this->dbName, $this->path);
            }
          
            
        }
        
    }

    private function restoreDb($dbUser, $dbPassword, $host, $dbName, $path)
    {
        $command = sprintf('mysql -u %s -p\'%s\' -h %s %s < %s', $dbUser, $dbPassword, $host, $dbName, $path);
        $this->info("Restoring Database...");        
        exec($command, $output, $return);
        if(!$return)
        {
            $this->info("Database successfully restored.");            
        }
        else
        {            
            throw new Exception( "An ERROR occured while restoring database!" );
        }
    }
    
    protected function setNamespace()
    {
        $this->namespace = \Vizrex\LaravelDbUtils\LaravelDbUtilsProvider::getNamespace();
    }
}
