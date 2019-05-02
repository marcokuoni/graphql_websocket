<?php
namespace Concrete\Package\Concrete5GraphqlWebsocket;

use Concrete\Core\Http\ServerInterface;
use Concrete\Core\Package\Package;
use Custom\Space\Middleware;
use Page;
use SinglePage;

class Controller extends Package
{

    protected $appVersionRequired = '8.5.1';
    protected $pkgVersion = '0.2';
    protected $pkgHandle = 'concrete5_graphql_websocket';
    protected $pkgName = 'GraphQL with Websocket';
    protected $pkgDescription = 'This Package brings the power of GraphQL and Websockets to Concrete5';
    protected $pkg;
    protected $pkgAutoloaderRegistries = array(
        'src/Concrete5GraphqlWebsocket/GraphQl' => '\Concrete5GraphqlWebsocket\GraphQl',
    );

    public function on_start()
    {
        // Extend the ServerInterface binding so that when concrete5 creates the http server we can add our middleware
        $this->app->extend(ServerInterface::class, function(ServerInterface $server) {
            // Add our custom middleware
            return $server->addMiddleware($this->app->make(Middleware::class));
        });
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

}
