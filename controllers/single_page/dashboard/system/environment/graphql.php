<?php

namespace Concrete\Package\Concrete5GraphqlWebsocket\Controller\SinglePage\Dashboard\System\Environment;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Utility\Service\Validation\Numbers;
use Concrete5GraphqlWebsocket\GraphQl\SchemaBuilder;
use Concrete5GraphqlWebsocket\GraphQl\WebsocketHelpers;
use Concrete5GraphqlWebsocket\PackageHelpers;
use Exception;

class Graphql extends DashboardPageController
{
    public function view()
    {
        $config = PackageHelpers::getFileConfig($this->app);
        $websocket_servers = (array) ($config->get('websocket.servers'));
        $this->set('websocket_servers', $websocket_servers);
        $this->set('websocket_has_servers', (bool) (count(array_keys($websocket_servers)) > 0));
        $this->set('websocket_debug', (bool) $config->get('websocket.debug'));
        $this->set('graphql_dev_mode', (bool) $config->get('graphql.graphql_dev_mode'));
        $max_query_complexity = (int) $config->get('graphql.max_query_complexity');
        $this->set('max_query_complexity', $max_query_complexity);
        $this->set('query_complexity_analysis', (bool) ($max_query_complexity > 0));
        $max_query_depth = (int) $config->get('graphql.max_query_depth');
        $this->set('max_query_depth', $max_query_depth);
        $this->set('limiting_query_depth', (bool) ($max_query_depth > 0));
        $this->set('disabling_introspection', (bool) $config->get('graphql.disabling_introspection'));
    }

    public function update_entity_settings()
    {
        if (!$this->token->validate('update_entity_settings')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            if ($this->isPost()) {
                $config = PackageHelpers::getFileConfig($this->app);
                $restartWebsocket = ' ' . t('Restart the websocket server with the button on the footer to refresh also there GraphQL schema');
                $gdm = $this->post('GRAPHQL_DEV_MODE') === 'yes';
                $wd = $this->post('WEBSOCKET_DEBUG') === 'yes';
                $w = $this->post('WEBSOCKET') === 'yes';
                $qca = $this->post('QUERY_COMPLEXITY_ANALYSIS') === 'yes';
                $lqd = $this->post('LIMITING_QUERY_DEPTH') === 'yes';
                $di = $this->post('DISABLING_INTROSPECTION') === 'yes';

                if ($this->request->request->get('refresh')) {
                    SchemaBuilder::refreshSchemaMerge();
                    $this->flash('success', t('GraphQL cache cleared, GraphQL schema updated.') . ($w ? $restartWebsocket : ''));
                    $this->redirect('/dashboard/system/environment/graphql', 'view');
                } else {
                    $config->save('graphql.graphql_dev_mode', $gdm);
                    $config->save('graphql.disabling_introspection', $di);
                    if ($qca) {
                        //this is a auto setting from graphql php, just set it to true that the user has feedback
                        $config->save('graphql.disabling_introspection', true);
                        $config->save('graphql.max_query_complexity', (int) $this->post('MAX_QUERY_COMPLEXITY'));
                    } else {
                        $config->save('graphql.max_query_complexity', 0);
                    }
                    if ($lqd) {
                        //this is a auto setting from graphql php, just set it to true that the user has feedback
                        $config->save('graphql.disabling_introspection', true);
                        $config->save('graphql.max_query_depth', (int) $this->post('MAX_QUERY_DEPTH'));
                    } else {
                        $config->save('graphql.max_query_depth', 0);
                    }
                    if ($gdm) {
                        SchemaBuilder::refreshSchemaMerge();
                    }

                    if ($w) {
                        $config->save('websocket.debug', $wd);
                        $servers = (array) ($config->get('websocket.servers'));
                        $config->save('websocket.servers', []);
                        $websocketsPorts = $this->post('WEBSOCKET_PORTS');
                        foreach ($websocketsPorts as $websocketsPort) {
                            $hasServerAlready = false;
                            foreach ($servers as $port => $pid) {
                                if ($port == $websocketsPort) {
                                    $hasServerAlready = true;
                                    $config->save('websocket.servers.' . $websocketsPort, $pid);
                                }
                            }
                            if (!$hasServerAlready) {
                                $config->save('websocket.servers.' . $websocketsPort, '');
                            }
                        }
                    } else {
                        $config->save('websocket.debug', false);
                        $servers = (array) ($config->get('websocket.servers'));
                        $config->save('websocket.servers', []);
                        foreach ($servers as $port => $pid) {
                            $pid = (int) $pid;
                            if ($pid > 0) {
                                WebsocketHelpers::stop($pid);
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
        $config = PackageHelpers::getFileConfig($this->app);

        if (!$this->token->validate('ccm-restart_websockets')) {
            throw new Exception($this->token->getErrorMessage());
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

        $success = true;
        foreach ($pids as $pid) {
            $pid = (int) $pid;
            $currentPort = 0;

            $servers = (array) ($config->get('websocket.servers'));
            foreach ($servers as $port => $oldPid) {
                if ($pid == $oldPid) {
                    $currentPort = $port;
                    $config->save('websocket.servers.' . $port, '');
                }
            }
            $success &= WebsocketHelpers::stop($pid);

            if (!$success) {
                throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
            } elseif ($currentPort > 0) {
                WebsocketHelpers::start($currentPort);
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
        $config = PackageHelpers::getFileConfig($this->app);

        if (!$this->token->validate('ccm-stop_websocket')) {
            throw new Exception($this->token->getErrorMessage());
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

        $success = true;
        foreach ($pids as $pid) {
            $pid = (int) $pid;

            $servers = (array) ($config->get('websocket.servers'));
            foreach ($servers as $port => $oldPid) {
                if ($pid == $oldPid) {
                    $config->save('websocket.servers.' . $port, '');
                }
            }
            $success &= WebsocketHelpers::stop($pid);

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
            throw new Exception($this->token->getErrorMessage());
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

        foreach ($ports as $port) {
            $port = (int) $port;
            WebsocketHelpers::start($port);
            sleep(1);
        }

        $this->flash('success', t('Websocket server started and GraphQL Schema reloaded. Refresh this site to get the new pids if you did not get it already.'));

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function removeWebsocketServer()
    {
        $config = PackageHelpers::getFileConfig($this->app);

        if (!$this->token->validate('ccm-remove_websocket')) {
            throw new Exception($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPid = $this->request->request->get('pid');
        $rawPort = $this->request->request->get('port');

        if (!$valn->integer($rawPort, 0)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'port'));
        }
        $port = (int) $rawPort;
        $pid = (int) $rawPid;

        $servers = (array) ($config->get('websocket.servers'));
        $config->save('websocket.servers', []);
        foreach ($servers as $oldPort => $oldPid) {
            if ($pid > 0 && $pid == $oldPid) {
                $success = WebsocketHelpers::stop($pid);

                if (!$success) {
                    throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
                }
            } elseif ($port !== $oldPort) {
                $config->save('websocket.servers.' . $oldPort, $oldPid);
            }
        }

        if (!$pid || $success) {
            $this->flash('success', t('Websocket server removed'));
        } elseif ($pid > 0) {
            $this->flash('error', t('Could not remove websocket server'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }
}
