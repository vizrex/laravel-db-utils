<?php


namespace Vizrex\LaravelDbUtils;
use Vizrex\Laraviz\BaseServiceProvider;


class LaravelDbUtilsProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        \Storage::extend('google', function($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);
            $service = new \Google_Service_Drive($client);
            $adapter = new \Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter($service, $config['folderId']);
            return new \League\Flysystem\Filesystem($adapter);
        });               
        
        $this->loadTranslationsFrom(__DIR__."/../resources/lang", self::getNamespace());
        
        $this->publishes([
        __DIR__.'/../config/dbutils.php' => config_path('dbutils.php')        
        ]);
        
        $this->commands([
           'Vizrex\LaravelDbUtils\Console\Commands\BackupDatabase',
           'Vizrex\LaravelDbUtils\Console\Commands\RestoreDatabase',
           'Vizrex\LaravelDbUtils\Console\Commands\CreateDatabase'
           ]);
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {        
        $this->app->bind('dotenv-editor', 'Jackiedo\DotenvEditor\DotenvEditor');
    }
}