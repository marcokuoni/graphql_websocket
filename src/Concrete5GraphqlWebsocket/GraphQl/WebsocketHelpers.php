<?php
namespace Concrete5GraphqlWebsocket\GraphQl;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Support\Facade\Facade;
use Siler\GraphQL as SilerGraphQL;

class WebsocketHelpers
{
    public static function start($port)
    {
        $app = Facade::getFacadeApplication();
        $phpVersion = substr(PHP_VERSION,0,3);

        if ((bool)$app->make('config')->get('concrete.websocket.debug')) {
            $cmd = escapeshellcmd("/usr/bin/php".$phpVersion." ".DIR_BASE."/index.php --websocket-port ".$port." >> /var/log/subscription_server.log 2>&1 &");
        } else {
            $cmd = escapeshellcmd("/usr/bin/php".$phpVersion." ".DIR_BASE."/index.php --websocket-port ".$port." > /dev/null 2>/dev/null &");
        }
        
        shell_exec($cmd);
    }

    public static function stop($pid)
    {
        $command = 'kill ' . $pid;
        exec($command);
        if (self::status($pid) == false) return true;
        else return false;
    }

    public static function status($pid)
    {
        $command = 'ps -p ' . $pid;
        exec($command, $op);
        if (!isset($op[1])) return false;
        else return true;
    }

    public static function setSubscriptionAt() {
        $app = Facade::getFacadeApplication();
        $config = $app->make('config');
        $servers = (array)$config->get('concrete.websocket.servers');
        foreach ($servers as $port => $pid) {
            $port = (int)$port;
            $pid = (int)$pid;

            if ($pid > 0) {
                if ($port > 0) {
                    SilerGraphQL\subscriptions_at('ws://127.0.0.1:' . $port . '/');
                } else {
                    SilerGraphQL\subscriptions_at('ws://127.0.0.1:3000/');
                }
            }
        }
    }
}