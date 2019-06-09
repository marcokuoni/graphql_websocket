<?php

namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Facade;
use Concrete5GraphqlWebsocket\PackageHelpers;
use Events;
use Siler\GraphQL as SilerGraphQL;

class Websocket
{
    public static function run()
    {
        $app = Facade::getFacadeApplication();
        if ($app->isRunThroughCommandLineInterface()) {
            //child process, we check for websocket server port from argv.
            //You can start a websocket server by 'php index.php --websocket-port 3000
            $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
            $hasPort = false;
            foreach ($args as $arg) {
                if ($hasPort) {
                    $port = (int) $arg;
                    $hasPort = false;
                }
                if ($arg === '--websocket-port' || $arg === '-wp') {
                    $hasPort = true;
                }
            }
            if ($port > 0) {
                //After all on_start (when all graphql schemas are built) start the server
                Events::addListener('on_before_console_run', function ($event) {
                    $app = Facade::getFacadeApplication();
                    $config = PackageHelpers::getFileConfig($app);
                    $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
                    $hasPort = false;
                    foreach ($args as $arg) {
                        if ($hasPort) {
                            $port = (int) $arg;
                            $hasPort = false;
                        }
                        if ($arg === '--websocket-port' || $arg === '-wp') {
                            $hasPort = true;
                        }
                    }
                    if ($port > 0) {
                        $connection = @fsockopen('127.0.0.1', $port);

                        if (is_resource($connection)) {
                            fclose($connection);
                        } else {
                            $schema = \Concrete5GraphqlWebsocket\SchemaBuilder::get();

                            if ($schema && $port > 0) {
                                if ($config->get('websocket.debug')) {
                                    echo 'start running server with pid ' . posix_getpid() . ' on 127.0.0.1:' . $port . ' at ' . date(DATE_ATOM) . "\n";
                                }
                                $config->save('websocket.servers.' . $port, posix_getpid());
                                SilerGraphQL\subscriptions($schema, [], '127.0.0.1', $port)->run();
                            }
                        }
                    }
                });
            }
        }
    }
}
