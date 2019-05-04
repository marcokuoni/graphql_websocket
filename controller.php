<?php
namespace Concrete\Package\Concrete5GraphqlWebsocket;

use Concrete\Core\Http\ServerInterface;
use Concrete\Core\Package\Package;
use Custom\Space\Middleware;
use Page;
use SinglePage;
use Route;
use Concrete5GraphqlWebsocket\GraphQl\Websocket;
use Concrete5GraphqlWebsocket\GraphQl\WebsocketHelpers;
use Concrete5GraphqlWebsocket\GraphQl\SchemaBuilder;

class Controller extends Package
{

    protected $appVersionRequired = '8.5.1';
    protected $pkgVersion = '1.0.2';
    protected $pkgHandle = 'concrete5_graphql_websocket';
    protected $pkgName = 'GraphQL with Websocket';
    protected $pkgDescription = 'This Package brings the power of GraphQL and Websockets to Concrete5';
    protected $pkg;
    protected $pkgAutoloaderRegistries = array(
        'src/Concrete5GraphqlWebsocket/GraphQl' => '\Concrete5GraphqlWebsocket\GraphQl',
    );

    public function on_start()
    {
        Route::register('/graphql', 'Concrete5GraphqlWebsocket\GraphQl\Api::view');
        
        // Extend the ServerInterface binding so that when concrete5 creates the http server we can add our middleware
        $this->app->extend(ServerInterface::class, function(ServerInterface $server) {
            // Add our custom middleware
            return $server->addMiddleware($this->app->make(Middleware::class));
        });

        Websocket::run();
    }
	
    public function install() {
        $this->pkg = parent::install();	
        $this->addSinglePages();
    }
	
    public function upgrade(){
        $result = parent::upgrade();
        $this->pkg = Package::getByHandle($this->pkgHandle);
        $this->addSinglePages();
        return $result;
    }

    public function uninstall()
    { 
        $this->app->make('config')->save('concrete.websocket.debug', false);
        $servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
        $this->app->make('config')->save('concrete.websocket.servers', array());
        foreach ($servers as $port => $pid) {
            $pid = (int)$pid;
            if ($pid > 0) {
                WebsocketHelpers::stop($pid);
            }
        }

        $this->removeSinglePage('/dashboard/system/environment/graphql');
        SchemaBuilder::deleteSchemaFile();

        parent::uninstall();
    }
    
    private function addSinglePages(){
        $this->addSinglePage('/dashboard/system/environment/graphql', t('GraphQL / Websocket'), t('Change the settings for GraphQL and the Websocket Servers'));
    }
    
    private function addSinglePage($path, $name, $description){
        $singlePage = Page::getByPath($path);
        if( !is_object($singlePage) || !intval($singlePage->getCollectionID()) ){
            $singlePage = SinglePage::add($path, $this->pkg);
        }
        if( is_object($singlePage) && intval($singlePage->getCollectionID()) ){
            $singlePage->update(array('cName'=>$name, 'cDescription'=>$description));
        }else{
            throw new Exception( t('Error: ' . $path . ' page not created'));
        }
    }

    private function removeSinglePage($path) {
        $singlePage = Page::getByPath($path);
        if( is_object($singlePage) && intval($singlePage->getCollectionID()) ){
            $singlePage->delete();
        }
    }

}
