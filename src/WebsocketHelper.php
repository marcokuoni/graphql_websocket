<?php

namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;

class WebsocketHelper
{
    public static function autostartWebsocketServers()
    {
        $config = App::make('config');

        $autostart = (bool) $config->get('concrete5_graphql_websocket::websocket.autostart');
        if ($autostart) {
            $servers = (array) $config->get('concrete5_graphql_websocket::websocket.servers');
            $websocketService = App::make(WebsocketService::class);

            foreach ($servers as $port => $pid) {
                $websocketService->start((int) $port);
            }
        }
    }
}
