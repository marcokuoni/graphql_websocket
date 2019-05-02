<?php
namespace Concrete\Package\Concrete5GraphqlWebsocket;

use Concrete\Core\Http\ServerInterface;
use Concrete\Core\Package\Package;
use Custom\Space\Middleware;

class Controller extends Package
{

    protected $appVersionRequired = '8.5.1';
    protected $pkgVersion = '0.1';
    protected $pkgHandle = 'concrete5_graphql_websocket';
    protected $pkgName = 'GraphQL with Websocket';
    protected $pkgDescription = 'This Package brings the power of GraphQL and Websockets to Concrete5';

    public function on_start()
    {
        // Extend the ServerInterface binding so that when concrete5 creates the http server we can add our middleware
        $this->app->extend(ServerInterface::class, function(ServerInterface $server) {
            // Add our custom middleware
            return $server->addMiddleware($this->app->make(Middleware::class));
        });
    }

}
