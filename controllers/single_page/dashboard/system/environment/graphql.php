<?php

namespace Concrete\Package\Concrete5GraphqlWebsocket\Controller\SinglePage\Dashboard\System\Environment;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Utility\Service\Validation\Numbers;
use Concrete5GraphqlWebsocket\SchemaBuilder;
use Concrete5GraphqlWebsocket\WebsocketService;
use Exception;
use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\User\User;

class Graphql extends DashboardPageController
{
    public function view()
    {
        $config = $this->app->make('config');
        $currentUser = App::make(User::class);
        if ((int) $currentUser->getUserID() === 1) {
            $auth_secret_key = (string) $config->get('concrete5_graphql_websocket::graphql.auth_secret_key');
            $this->set('auth_secret_key', $auth_secret_key);

            $corsOrigins = (string) implode(', ', $config->get('concrete5_graphql_websocket::graphql.corsOrigins'));
            $this->set('corsOrigins', $corsOrigins);
        }
        $websocket_servers = (array) $config->get('concrete5_graphql_websocket::websocket.servers');
        $this->set('websocket_servers', $websocket_servers);
        $this->set('websocket_has_servers', (bool) (count(array_keys($websocket_servers)) > 0));
        $this->set('websocket_debug', (bool) $config->get('concrete5_graphql_websocket::websocket.debug'));
        $this->set('websocket_autostart', (bool) $config->get('concrete5_graphql_websocket::websocket.autostart'));
        $this->set('graphql_dev_mode', (bool) $config->get('concrete5_graphql_websocket::graphql.graphql_dev_mode'));
        $max_query_complexity = (int) $config->get('concrete5_graphql_websocket::graphql.max_query_complexity');
        $this->set('max_query_complexity', $max_query_complexity);
        $this->set('query_complexity_analysis', (bool) ($max_query_complexity > 0));
        $max_query_depth = (int) $config->get('concrete5_graphql_websocket::graphql.max_query_depth');
        $this->set('max_query_depth', $max_query_depth);
        $this->set('limiting_query_depth', (bool) ($max_query_depth > 0));
        $this->set('disabling_introspection', (bool) $config->get('concrete5_graphql_websocket::graphql.disabling_introspection'));
        $this->set('logPath', str_replace('/', DIRECTORY_SEPARATOR, $config->get('concrete5_graphql_websocket::websocket.debug_log')));
        $auth_server_url = (String) $config->get('concrete5_graphql_websocket::graphql.auth_server_url');
        $this->set('auth_server_url', $auth_server_url);
    }

    public function update_entity_settings()
    {
        if (!$this->token->validate('update_entity_settings')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            if ($this->isPost()) {
                $config = $this->app->make('config');
                $restartWebsocket = ' ' . t('Restart the websocket server with the button on the footer to refresh also there GraphQL schema');
                $gdm = $this->post('GRAPHQL_DEV_MODE') === 'yes';
                $wd = $this->post('WEBSOCKET_DEBUG') === 'yes';
                $wa = $this->post('WEBSOCKET_AUTOSTART') === 'yes';
                $w = $this->post('WEBSOCKET') === 'yes';
                $qca = $this->post('QUERY_COMPLEXITY_ANALYSIS') === 'yes';
                $lqd = $this->post('LIMITING_QUERY_DEPTH') === 'yes';
                $di = $this->post('DISABLING_INTROSPECTION') === 'yes';
                $auth_server_url = (String) $this->post('auth_server_url');
                $corsOrigins = (string) $this->post('corsOrigins');

                if ($this->request->request->get('refresh')) {
                    SchemaBuilder::refreshSchemaMerge();
                    $this->flash('success', t('GraphQL cache cleared, GraphQL schema updated.') . ($w ? $restartWebsocket : ''));
                    $this->redirect('/dashboard/system/environment/graphql', 'view');
                } else {
                    $config->save('concrete5_graphql_websocket::graphql.graphql_dev_mode', $gdm);
                    $config->save('concrete5_graphql_websocket::graphql.disabling_introspection', $di);
                    if ($qca) {
                        //this is a auto setting from graphql php, just set it to true that the user has feedback
                        $config->save('concrete5_graphql_websocket::graphql.disabling_introspection', true);
                        $config->save('concrete5_graphql_websocket::graphql.max_query_complexity', (int) $this->post('MAX_QUERY_COMPLEXITY'));
                    } else {
                        $config->save('concrete5_graphql_websocket::graphql.max_query_complexity', 0);
                    }
                    if ($lqd) {
                        //this is a auto setting from graphql php, just set it to true that the user has feedback
                        $config->save('concrete5_graphql_websocket::graphql.disabling_introspection', true);
                        $config->save('concrete5_graphql_websocket::graphql.max_query_depth', (int) $this->post('MAX_QUERY_DEPTH'));
                    } else {
                        $config->save('concrete5_graphql_websocket::graphql.max_query_depth', 0);
                    }
                    if ($gdm) {
                        SchemaBuilder::refreshSchemaMerge();
                    }

                    $currentUser = App::make(User::class);
                    if ((int) $currentUser->getUserID() === 1) {
                        $config->save('concrete5_graphql_websocket::graphql.auth_secret_key', (string) $this->post('auth_secret_key'));

                        if (isset($corsOrigins) && $corsOrigins !== '') {
                            $config->save('concrete5_graphql_websocket::graphql.corsOrigins', explode(',', $corsOrigins));
                        }
                    }
                    if (isset($auth_server_url) && $auth_server_url !== '') {
                        $config->save('concrete5_graphql_websocket::graphql.auth_server_url', $auth_server_url);
                    }

                    if ($w) {
                        $config->save('concrete5_graphql_websocket::websocket.debug', $wd);
                        $config->save('concrete5_graphql_websocket::websocket.autostart', $wa);
                        $servers = (array) $config->get('concrete5_graphql_websocket::websocket.servers');
                        $config->save('concrete5_graphql_websocket::websocket.servers', []);
                        $websocketsPorts = $this->post('WEBSOCKET_PORTS');
                        foreach ($websocketsPorts as $websocketsPort) {
                            $hasServerAlready = false;
                            foreach ($servers as $port => $pid) {
                                if ($port == $websocketsPort) {
                                    $hasServerAlready = true;
                                    $config->save('concrete5_graphql_websocket::websocket.servers.' . $websocketsPort, $pid);
                                }
                            }
                            if (!$hasServerAlready) {
                                $config->save('concrete5_graphql_websocket::websocket.servers.' . $websocketsPort, '');
                            }
                        }
                    } else {
                        $config->save('concrete5_graphql_websocket::websocket.debug', false);
                        $config->save('concrete5_graphql_websocket::websocket.autostart', false);
                        $servers = (array) ($config->get('concrete5_graphql_websocket::websocket.servers'));
                        $config->save('concrete5_graphql_websocket::websocket.servers', []);
                        $websocketService = $this->app->make(WebsocketService::class);
                        foreach ($servers as $pid) {
                            $pid = (int) $pid;
                            if ($pid > 0) {
                                $websocketService->stop($pid);
                            }
                        }
                    }
                    $this->flash('success', t('Settings updated.') . ($w && $gdm ? $restartWebsocket : ''));
                    $this->redirect('/dashboard/system/environment/graphql', 'view');
                }
            }
        } else {
            $this->set('error', [$this->token->getErrorMessage()]);
        }
    }

    public function restartWebsocketServer()
    {
        $config = $this->app->make('config');

        if (!$this->token->validate('ccm-restart_websockets')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPids = $this->request->request->get('pids');
        if (!is_array($rawPids)) {
            throw new UserMessageException(sprintf('Invalid parameters: %s', 'pids'));
        }
        $pids = [];
        foreach ($rawPids as $rawPid) {
            if (!$valn->integer($rawPid, 0)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
            }
            $pid = (int) $rawPid;
            if (in_array($pid, $pids, true)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
            }
            $pids[] = $pid;
        }

        $websocketService = $this->app->make(WebsocketService::class);
        $success = true;
        foreach ($pids as $pid) {
            $pid = (int) $pid;
            $currentPort = 0;

            $servers = (array) ($config->get('concrete5_graphql_websocket::websocket.servers'));
            foreach ($servers as $port => $oldPid) {
                if ($pid == $oldPid) {
                    $currentPort = $port;
                    $config->save('concrete5_graphql_websocket::websocket.servers.' . $port, '');
                }
            }
            $success &= $websocketService->stop($pid);

            if (!$success) {
                throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
            }
            if ($currentPort > 0) {
                $websocketService->start($currentPort);
            }
        }
        if ($success) {
            $this->flash('success', t('Websocket server restarted and GraphQL Schema reloaded. Refresh this site to get the new pids if you did not get it already.'));
        } else {
            $this->flash('error', t('Did not work use "sudo kill pid" on the server console and refresh this site afterwards.'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function stopWebsocketServer()
    {
        $config = $this->app->make('config');

        if (!$this->token->validate('ccm-stop_websocket')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPids = $this->request->request->get('pids');
        if (!is_array($rawPids)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
        }
        $pids = [];
        foreach ($rawPids as $rawPid) {
            if (!$valn->integer($rawPid, 0)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
            }
            $pid = (int) $rawPid;
            if (in_array($pid, $pids, true)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
            }
            $pids[] = $pid;
        }

        $websocketService = $this->app->make(WebsocketService::class);
        $success = true;
        foreach ($pids as $pid) {
            $pid = (int) $pid;

            $servers = (array) ($config->get('concrete5_graphql_websocket::websocket.servers'));
            foreach ($servers as $port => $oldPid) {
                if ($pid == $oldPid) {
                    $config->save('concrete5_graphql_websocket::websocket.servers.' . $port, '');
                }
            }
            $success &= $websocketService->stop($pid);

            if (!$success) {
                throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
            }
        }
        if ($success) {
            $this->flash('success', t('Websocket server stopped.'));
        } else {
            $this->flash('error', t('Did not work use "sudo kill pid" on the server console and refresh this site afterwards.'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function startWebsocketServer()
    {
        if (!$this->token->validate('ccm-start_websocket')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPorts = $this->request->request->get('ports');
        if (!is_array($rawPorts)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'ports'));
        }
        $ports = [];
        foreach ($rawPorts as $rawPort) {
            if (!$valn->integer($rawPort, 0)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'ports'));
            }
            $port = (int) $rawPort;
            if (in_array($port, $ports, true)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'ports'));
            }
            $ports[] = $port;
        }
        $websocketService = $this->app->make(WebsocketService::class);
        foreach ($ports as $port) {
            $port = (int) $port;
            $websocketService->start($port);
            sleep(1);
        }

        $this->flash('success', t('Websocket server started and GraphQL Schema reloaded. Refresh this site to get the new pids if you did not get it already.'));

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function removeWebsocketServer()
    {
        $config = $this->app->make('config');

        if (!$this->token->validate('ccm-remove_websocket')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPid = $this->request->request->get('pid');
        $rawPort = $this->request->request->get('port');

        if (!$valn->integer($rawPort, 0)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'port'));
        }
        $port = (int) $rawPort;
        $pid = (int) $rawPid;

        $servers = (array) $config->get('concrete5_graphql_websocket::websocket.servers');
        $config->save('concrete5_graphql_websocket::websocket.servers', []);
        $websocketService = $this->app->make(WebsocketService::class);
        foreach ($servers as $oldPort => $oldPid) {
            if ($pid > 0 && $pid == $oldPid) {
                $success = $websocketService->stop($pid);

                if (!$success) {
                    throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
                }
            } elseif ($port !== $oldPort) {
                $config->save('concrete5_graphql_websocket::websocket.servers.' . $oldPort, $oldPid);
            }
        }

        if (!$pid || $success) {
            $this->flash('success', t('Websocket server removed'));
        } elseif ($pid > 0) {
            $this->flash('error', t('Could not remove websocket server'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function getConnectedClients()
    {
        if (!$this->token->validate('ccm-count-clients')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $ports = $this->request->request->get('ports');
        $ports = is_array($ports) ? array_filter(array_map('intval', $ports)) : [];
        if ($ports === []) {
            throw new Exception(sprintf('Invalid parameters: %s', 'ports'));
        }
        $websocketService = $this->app->make(WebsocketService::class);
        $result = [];
        foreach ($ports as $port) {
            $result[$port] = $websocketService->getClientsCount($port);
        }

        return $this->app->make(ResponseFactoryInterface::class)->json($result);
    }
}
