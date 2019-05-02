<?php
namespace Concrete5GraphqlWebsocket\GraphQl;

defined('C5_EXECUTE') or die("Access Denied.");

use Siler\GraphQL as SilerGraphQL;
use Concrete\Core\Support\Facade\Facade;
use Events;

class Websocket
{
    public static function run()
    {
        $app = Facade::getFacadeApplication();
        $config = $app->make('config');
        $servers = (array)$config->get('concrete.websocket.servers');

        foreach ($servers as $port => $pid) {
            $port = (int)$port;
            if ($port > 0) {
                $connection = @fsockopen('127.0.0.1', $port);

                if (is_resource($connection)) {
                    fclose($connection);
                } else {
                    if (!$app->isRunThroughCommandLineInterface()) {
                        //Starts a new concrete5 instance from console to run the websocket server
                        if ((bool)$config->get('concrete.websocket.debug')) {
                            shell_exec("/usr/bin/php " . DIR_BASE . "/index.php --websocket-port " . $port . " >> /var/log/subscription_server.log 2>&1 &");
                        } else {
                            shell_exec("/usr/bin/php " . DIR_BASE . "/index.php --websocket-port " . $port . " > /dev/null 2>/dev/null &");
                        }
                    } else if ($app->isRunThroughCommandLineInterface()) {
                        //child process, we take port from argv. So the posabillity exists to start more then one child process
                        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
                        $hasPort = false;
                        foreach ($args as $arg) {
                            if ($hasPort) {
                                $port = (int)$arg;
                                $hasPort = false;
                            }
                            if ($arg === '--websocket-port' || $arg === '-wp') {
                                $hasPort = true;
                            }
                        }
                        if ($port > 0) {
                            //After all on_start (schemas built) start the server
                            Events::addListener('on_before_console_run', function ($event) {
                                $app = Facade::getFacadeApplication();
                                $config = $app->make('config');
                                $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
                                $hasPort = false;
                                foreach ($args as $arg) {
                                    if ($hasPort) {
                                        $port = (int)$arg;
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
                                        $schema = \Concrete5GraphqlWebsocket\GraphQl\SchemaBuilder::get();

                                        if ($schema && $port > 0) {
                                            if ($config->get('concrete.websocket.debug')) {
                                                echo 'start running server with pid ' . posix_getpid() . ' on 127.0.0.1:' . $port . ' at ' . date(DATE_ATOM) . "\n";
                                            }
                                            $config->save('concrete.websocket.servers.' . $port, posix_getpid());
                                            SilerGraphQL\subscriptions($schema, [], '127.0.0.1', $port)->run();
                                        }
                                    }
                                }
                            });
                        }
                    }
                }
            }
        }
    }
}
