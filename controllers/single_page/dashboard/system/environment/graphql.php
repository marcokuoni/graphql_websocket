<?php
namespace Concrete\Package\Concrete5GraphqlWebsocket\Controller\SinglePage\Dashboard\System\Environment;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete5GraphqlWebsocket\GraphQL\SchemaBuilder;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Utility\Service\Validation\Numbers;
use Exception;

class Graphql extends DashboardPageController
{
    public function view()
    {
        $websocket_servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
        $this->set('websocket_servers', $websocket_servers);
        $this->set('websocket_has_servers', (bool)(count(array_keys($websocket_servers)) > 0));
        $this->set('websocket_debug', (bool)$this->app->make('config')->get('concrete.websocket.debug'));
        $this->set('graphql_dev_mode', (bool)$this->app->make('config')->get('concrete.cache.graphql_dev_mode'));
    }

    public function update_entity_settings()
    {
        if (!$this->token->validate('update_entity_settings')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            if ($this->isPost()) {
                $restartWebsocket = ' ' . t('Restart the websocket server with the button on the footer');
                $gdm = $this->post('GRAPHQL_DEV_MODE') === 'yes';
                $wd = $this->post('WEBSOCKET_DEBUG') === 'yes';
                $w = $this->post('WEBSOCKET') === 'yes';

                if ($this->request->request->get('refresh')) {
                    SchemaBuilder::refreshSchemaMerge();
                    $this->flash('success', t('GraphQL cache cleared, GraphQL schema updated.') . ($w ? $restartWebsocket : ''));
                    $this->redirect('/dashboard/system/environment/graphql', 'view');
                } else {
                    $this->app->make('config')->save('concrete.cache.graphql_dev_mode', $gdm);

                    if ($w) {
                        $this->app->make('config')->save('concrete.websocket.debug', $wd);
                        $servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
                        $websocketsPorts = $this->post('WEBSOCKET_PORTS');
                        $newPorts = array();
                        foreach ($websocketsPorts as $websocketsPort) {
                            $hasServerAlready = false;
                            foreach ($servers as $port => $pid) {
                                if ($port == $websocketsPort) {
                                    $hasServerAlready = true;
                                }
                            }
                            if (!$hasServerAlready) {
                                $this->app->make('config')->save('concrete.websocket.servers.' . $websocketsPort, '');
                                $newPorts[] = $websocketsPort;
                            }
                        }
                        foreach ($newPorts as $newPort) {
                            $this->start($newPort);
                            // give the webservers a chance to start without reload
                            sleep(1);
                        }
                    } else {
                        $this->app->make('config')->save('concrete.websocket.debug', false);
                        $servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
                        $this->app->make('config')->save('concrete.websocket.servers', array());
                        foreach ($servers as $port => $pid) {
                            $pid = (int)$pid;
                            if ($pid > 0) {
                                $this->stop($pid);
                            }
                        }
                    }
                    $this->flash('success', t('Settings updated. Refresh the this site to get the new pids if you did not get it already.') . ($w && $gdm ? $restartWebsocket : ''));
                    $this->redirect('/dashboard/system/environment/graphql', 'view');
                }
            }
        } else {
            $this->set('error', [$this->token->getErrorMessage()]);
        }
    }

    public function restartWebsocketServer()
    {
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
            $pid = (int)$rawPid;
            if (in_array($pid, $pids, true)) {
                throw new Exception(sprintf('Invalid parameters: %s', 'pids'));
            }
            $pids[] = $pid;
        }

        $success = true;
        foreach ($pids as $pid) {
            $pid = (int)$pid;
            $currentPort = 0;

            $servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
            foreach ($servers as $port => $oldPid) {
                if ($pid == $oldPid) {
                    $currentPort = $port;
                    $this->app->make('config')->save('concrete.websocket.servers.' . $port, '');
                }
            }
            $success &= $this->stop($pid);

            if (!$success) {
                throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
            } else if ($currentPort > 0) {
                $this->start($currentPort);
            }
        }
        if ($success) {
            $this->flash('success', t('Websocket server restarted and GraphQL Schema reloaded. Refresh this site to get the new pids if you did not get it already.'));
        } else {
            $this->flash('error', t('Did not work use "sudo kill pid" on the server console and refresh this site afterwards.'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function removeWebsocketServer()
    {
        if (!$this->token->validate('ccm-remove_websocket')) {
            throw new Exception($this->token->getErrorMessage());
        }
        $valn = $this->app->make(Numbers::class);
        $rawPid = $this->request->request->get('pid');
        $rawPort = $this->request->request->get('port');

        if (!$valn->integer($rawPort, 0)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'port'));
        }
        $port = (int)$rawPort;

        if (!$valn->integer($rawPid, 0)) {
            throw new Exception(sprintf('Invalid parameters: %s', 'pid'));
        }
        $pid = (int)$rawPid;

        $servers = (array)($this->app->make('config')->get('concrete.websocket.servers'));
        $this->app->make('config')->save('concrete.websocket.servers', array());
        foreach ($servers as $oldPort => $oldPid) {
            if ($pid == $oldPid) {
                $success = $this->stop($pid);
    
                if (!$success) {
                    throw new Exception(sprintf('Did not work use "sudo kill %s" on the server console and refresh this site afterwards.', $pid));
                } 
            } else {
                $this->app->make('config')->save('concrete.websocket.servers.' . $oldPort, $oldPid);
            }
        }

        if ($success) {
            $this->flash('success', t('Websocket server removed'));
        } else {
            $this->flash('error', t('Could not remove websocket server'));
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    private function start($port)
    {
        if ((bool)$this->app->make('config')->get('concrete.websocket.debug')) {
            shell_exec("/usr/bin/php " . DIR_BASE . "/index.php --websocket-port " . $port . " >> /var/log/subscription_server.log 2>&1 &");
        } else {
            shell_exec("/usr/bin/php " . DIR_BASE . "/index.php --websocket-port " . $port . " > /dev/null 2>/dev/null &");
        }
    }

    private function stop($pid)
    {
        $command = 'kill ' . $pid;
        exec($command);
        if ($this->status($pid) == false) return true;
        else return false;
    }

    private function status($pid)
    {
        $command = 'ps -p ' . $pid;
        exec($command, $op);
        if (!isset($op[1])) return false;
        else return true;
    }
}
