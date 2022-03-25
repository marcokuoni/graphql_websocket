<?php

namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Controller\Controller;
use Siler\Http\Request;
use Siler\GraphQL as SilerGraphQL;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Concrete\Core\Support\Facade\Application as App;
use Symfony\Component\HttpFoundation\JsonResponse;

class Api extends Controller
{
    public function view()
    {
        $config = App::make('config');
        $origins = $config->get('concrete5_graphql_websocket::graphql.corsOrigins');
        if(in_array($_SERVER['HTTP_ORIGIN'], $origins)) {
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 600');
            header('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        }

        if (Request\method_is('options')){
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        }
        
        if (Request\method_is('post')) {
            App::make(EventDispatcherInterface::class)->dispatch('on_before_token_validate');

            $user = null;

            $tokenHelper = App::make(\Concrete5GraphqlWebsocket\TokenHelper::class);
            $token = $tokenHelper->getToken();
            if ($token) {
                try {
                    $user = $tokenHelper->validateToken($token);
                } catch (\Exception $e) {
                    return new JsonResponse($e, JsonResponse::HTTP_UNAUTHORIZED);
                }
            }

            if (Request\header('Content-Type') == 'application/json') {
                $data = Request\json('php://input');
            } else {
                $data = Request\post();
            }

            if (!is_array($data)) {
                throw new \UnexpectedValueException('Input should be a JSON object');
            }

            $schema = \Concrete5GraphqlWebsocket\SchemaBuilder::get();
            SilerGraphQL\init($schema, null, ['user' => $user]);
        }
    }
}
