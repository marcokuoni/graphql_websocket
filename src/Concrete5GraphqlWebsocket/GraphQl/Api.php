<?php
namespace Concrete5GraphqlWebsocket\GraphQl;

use Concrete\Core\Controller\Controller;
use Siler\GraphQL as SilerGraphQL;
use Siler\Http\Request;
use Siler\Http\Response;

class Api extends Controller
{
    public function view()
    {
        Response\header('Access-Control-Allow-Origin', '*');
        Response\header('Access-Control-Allow-Headers', 'content-type');
        
        if (Request\method_is('post')) {
            $schema = \Concrete\Core\GraphQl\SchemaBuilder::get();
            SilerGraphQL\init($schema);
        }
    }
}
