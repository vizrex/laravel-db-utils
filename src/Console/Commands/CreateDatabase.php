<?php

namespace Vizrex\LaravelDbUtils\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use DB;
use Illuminate\Support\Facades;
use Config;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

use Vizrex\Laraviz\Console\Commands\BaseCommand;

class CreateDatabase extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create
            {new_database}
            {username : A username that has CREATE TABLE privileges}
            {--password= : Password for the user}
            {--host= : Database host.}
            {--port= : Database port.}                        
            {--new_user= : If provided, a new user will be created and will be granted database privileges}
            {--new_password= : Password for the new user}
            {--set_env=true: Whether to write database variables to .env , default is true}
            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a database as per given parameters and options.';

//    private $dbName = null;
//    private $dbUser = null;
//    private $dbPassword = null;
//    private $host = null;
//    private $port = null;
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
        
        $dbName = $this->argument('new_database');
        
        $dbHostOption = $this->option('host');
        $dbHost = !empty($dbHostOption) ? $dbHostOption : config('dbutils.create.host');
        
        $dbPortOption = $this->option('port');
        $dbPort = !empty($dbPortOption) ? $dbPortOption : config('dbutils.create.port');
        
        $dbUser = $this->argument('username');
        $dbPassword = $this->option('password');
        
        $newUser = $this->option('new_user');
        $newPassword = $this->option('new_password');
        
        $setEnvOption = $this->option('set_env');
        $setEnv = empty($setEnvOption) ? true : $setEnvOption;
        
        //creating the database
        $this->info($this->str("creating_db", ['target' => $dbName]));
        $command = sprintf("echo 'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' | mysql -u %s --password=%s -h %s --port %s", $dbName, $dbUser, $dbPassword, $dbHost, $dbPort);
        self::runShellCommand($command, 'Database created successfully:', 'An ERROR occured while creating database', $dbName); 
                
        if(!empty($newUser))
        {   
            //creating new user
            $this->info($this->str('creating_new_user', ['target' => $newUser]));
            $command = sprintf('mysql -u %s --password=%s -h %s --port %s -e "CREATE USER %s@%s IDENTIFIED BY \'%s\'"', $dbUser, $dbPassword, $dbHost, $dbPort, $newUser, $dbHost, $newPassword );
            self::runShellCommand($command, 'New user has been created successfully:', 'An ERROR occured while creating new user', $newUser);
            
            //giving previllages
            $command = sprintf('mysql -u %s --password=%s -h %s --port %s -e "GRANT ALL PRIVILEGES ON %s.* TO %s@%s IDENTIFIED BY \'%s\'"', $dbUser, $dbPassword, $dbHost, $dbPort, $dbName, $newUser, $dbHost, $newPassword);
            self::runShellCommand($command, 'New user has been given previllages for newly created database:', 'An ERROR occured while creating new user');
        }      
        
        if($setEnv)
        {
            
            DotenvEditor::backup();
            dd('abc');
            $dbUserNameForEnv = $dbUser;
            $dbPasswordForEnv = $dbPassword;
            
            if(!empty($newUser))
            {
                $dbUserNameForEnv = $newUser;
                $dbPasswordForEnv = $newPassword;
            }
            
            DotenvEditor::setKeys([
                [
                    'key'     => 'DB_DATABASE',
                    'value'   => $dbName
                ],
                [
                    'key'     => 'DB_HOST',
                    'value'   => $dbHost
                ],
                [
                    'key'     => 'DB_PORT',
                    'value'   => $dbPort
                ],
                [
                    'key'     => 'DB_USERNAME',
                    'value'   => $dbUserNameForEnv
                ],
                [
                    'key'     => 'DB_PASSWORD',
                    'value'   => $dbPasswordForEnv
                ],
            ]);
            
            DotenvEditor::save();
            
        }
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
    
    protected function setNamespace()
    {
        $this->namespace = \Vizrex\LaravelDbUtils\LaravelDbUtilsProvider::getNamespace();
    }
    
   
}
