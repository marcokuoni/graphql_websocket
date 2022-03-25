<?php

namespace Concrete\Package\Concrete5GraphqlWebsocket;

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\RouterInterface;
use Concrete5GraphqlWebsocket\Console\WebsocketRunnerCommand;
use Concrete5GraphqlWebsocket\SchemaBuilder;
use Concrete5GraphqlWebsocket\WebsocketService;

class Controller extends Package
{
    protected $appVersionRequired = '8.5.1';
    protected $pkgVersion = '2.0.9';
    protected $pkgHandle = 'concrete5_graphql_websocket';
    protected $pkgName = 'GraphQL with Websocket';
    protected $pkgDescription = 'This Package brings the power of GraphQL and Websockets to Concrete5';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Concrete5GraphqlWebsocket',
    ];

    public function on_start()
    {
        $this->app->make(RouterInterface::class)->register('/graphql', 'Concrete5GraphqlWebsocket\Api::view');
        $this->registerAutoload();
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerCLICommands();
        }

        $this->app->singleton(\Concrete5GraphqlWebsocket\Helpers\HasAccess::class);
        $this->app->singleton(\Concrete5GraphqlWebsocket\Helpers\Cors::class);
        $websocketService = $this->app->make(WebsocketService::class);
        $websocketService->startListeningToOnConnect();
    }

    public function install()
    {
        parent::install();
        $this->installXML();
        $this->configureDefaultLogFile();
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->installXML();
    }

    public function uninstall()
    {
        $config = $this->app->make('config');
        $config->save('concrete5_graphql_websocket::websocket.debug', false);
        $servers = (array) $config->get('concrete5_graphql_websocket::websocket.servers');
        $websocketService = $this->app->make(WebsocketService::class);
        foreach ($servers as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $websocketService->stop($pid);
            }
        }
        $config->save('concrete5_graphql_websocket::websocket.servers', []);
        SchemaBuilder::deleteSchemaFile();

        parent::uninstall();
    }

    private function installXML()
    {
        $this->installContentFile('config/install.xml');
    }

    private function configureDefaultLogFile()
    {
        $config = $this->app->make('config');
        if ($config->get('concrete5_graphql_websocket::websocket.debug_log')) {
            return;
        }
        $logFile = '/var/log/subscription_server.log';
        if (DIRECTORY_SEPARATOR === '\\') {
            $dir = @sys_get_temp_dir();
            if ($dir) {
                $logFile = rtrim(str_replace('\\', '/', $dir), '/') . '/subscription_server.log';
            }
        }
        $config->save('concrete5_graphql_websocket::websocket.debug_log', $logFile);
    }

    private function registerAutoload()
    {
        $autoloader = $this->getPackagePath() . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
    }

    private function registerCLICommands()
    {
        $console = $this->app->make('console');
        $console->add(new WebsocketRunnerCommand());
    }
}
