<?php

namespace Concrete5GraphqlWebsocket;

use Firebase\JWT\JWT;
use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\Error\UserMessageException;
use Zend\Http\PhpEnvironment\Request;

class TokenHelper
{
    /**
     * Get the value of the Authorization header from the $_SERVER super global
     *
     * @return mixed|string
     */
    public function getToken()
    {
        $request = new Request();
        $authHeader = $request->getHeader('authorization') ? $request->getHeader('authorization')->toString() : null;
        if (!isset($authHeader)) {
            $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
        }
        $redirectAuthHeader = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
        $authHeader = $authHeader !== false ? $authHeader : ($redirectAuthHeader !== false ? $redirectAuthHeader : null);

        return $this->getTokenFromAuthHeader($authHeader);
    }

    public function getTokenFromAuthHeader($authHeader)
    {
        $token = '';
        if ($authHeader !== '') {
            list($token) = sscanf($authHeader, 'Bearer %s');
            if (!isset($token)) {
                list($token) = sscanf($authHeader, 'Authorization: Bearer %s');
            }
        }
        return $token;
    }

    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $secret = $this->getSecretKey();

        if (!$secret) {
            throw new UserMessageException(t('JWT is not configured properly'));
        }

        try {
            JWT::$leeway = 60;

            $token = !empty($token) ? JWT::decode($token, $secret, ['HS256']) : null;

            $config = App::make('config');
            $auth_server_url = (string) $config->get('concrete5_graphql_websocket::graphql.auth_server_url');
            if ($auth_server_url === '' || !isset($auth_server_url)) {
                $baseUrl = sprintf(
                    "%s://%s",
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                    $_SERVER['SERVER_NAME']
                );
            } else {
                $baseUrl = $auth_server_url;
            }
            // websocketserver has no server_name
            if (isset($_SERVER['SERVER_NAME']) && $baseUrl !== $token->iss) {
                throw new \Exception(t('The iss do not match with this server'));
            }
        } catch (\Exception $error) {
            if ($error->getMessage() === 'Expired token') {
                throw new UserMessageException(t('Expired token'), 401);
            } else {
                throw new UserMessageException($auth_server_url . t('The JWT Token is invalid').$token->iss, 401);
            }
        }

        return $token->data->user;
    }

    private function getSecretKey()
    {
        $config = App::make('config');
        $secret_key = (string) $config->get('concrete5_graphql_websocket::graphql.auth_secret_key');


        if (empty($secret_key)) {
            throw new UserMessageException(t('JWT Auth is not configured correctly. Please contact a site administrator.'));
        }
        return $secret_key;
    }
}
