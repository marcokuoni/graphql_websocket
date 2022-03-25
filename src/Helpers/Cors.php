<?php

namespace Concrete5GraphqlWebsocket\Helpers;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Support\Facade\Application as App;

class Cors
{
    public static function setCors()
    {
        $config = App::make('config');
        $origins = $config->get('concrete5_graphql_websocket::graphql.corsOrigins');
        if (in_array($_SERVER['HTTP_ORIGIN'], $origins)) {
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 600');
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            } else {
                header('Access-Control-Allow-Headers: Authorization, Content-Type');
            }
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Expose-Headers: *, Authorization');
        }
    }
}
date etag