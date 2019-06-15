<?php

namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Config\Repository\Repository;
use Siler\GraphQL as SilerGraphQL;

class WebsocketService
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var bool
     */
    protected $isWindows;

    /**
     * @param \Concrete\Core\Config\Repository\Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
        $this->isWindows = DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Start the gws:start CLI command with the specified port.
     *
     * @param int $port
     *
     * @return bool
     */
    public function start($port)
    {
        if ($this->isWindows) {
            $cmd = 'start /B ';
        } else {
            $cmd = '';
        }
        $cmd .= escapeshellarg($this->config->get('concrete5_graphql_websocket::websocket.php_exe'));
        $cmd .= ' ' . escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, DIR_BASE . '/concrete/bin/concrete5'));
        $cmd .= ' gws:start --no-interaction --no-ansi';
        $cmd .= ' ' . (int) $port;
        if ((bool) $this->config->get('concrete5_graphql_websocket::websocket.debug')) {
            $logFile = $this->config->get('concrete5_graphql_websocket::websocket.debug_log');
            $logFile = str_replace('/', DIRECTORY_SEPARATOR, $logFile);
            $cmd .= ' >> ' . escapeshellarg($logFile) . ' 2>&1';
        } else {
            if ($this->isWindows) {
                $cmd .= ' >NUL 2>NUL';
            } else {
                $cmd .= ' >/dev/null 2>/dev/null';
            }
        }
        if ($this->isWindows) {
            $handle = @popen($cmd, 'r');
            if (!is_resource($handle)) {
                return false;
            }
            pclose($handle);

            return true;
        }
        $cmd .= ' &';

        $output = null;
        $rc = -1;
        @exec($cmd, $output, $rc);

        return $rc === 0;
    }

    /**
     * Stop a process given its process ID.
     *
     * @param int $pid
     *
     * @return bool
     */
    public function stop($pid)
    {
        $pid = (int) $pid;
        if ($this->isWindows) {
            $command = "taskkill /PID {$pid} /F";
        } else {
            $command = "kill {$pid}";
        }
        exec($command);

        return $this->status($pid) == false;
    }

    /**
     * Check if a process with a specific ID is running.
     *
     * @param int $pid
     *
     * @return bool
     */
    public function status($pid)
    {
        $output = [];
        $rc = -1;
        $pid = (int) $pid;
        if ($this->isWindows) {
            exec("tasklist /FI \"PID eq {$pid}\" /FO CSV /NH", $output, $rc);

            return $rc === 0 && isset($output[0]) && $output[0] !== '' && $output[0][0] === '"';
        }
        exec("ps -p {$pid} 2>/dev/null", $output, $rc);

        return $rc === 0 && isset($output[1]);
    }

    public function setSubscriptionAt()
    {
        $servers = (array) $this->config->get('concrete5_graphql_websocket::websocket.servers');
        foreach ($servers as $port => $pid) {
            $port = (int) $port;
            $pid = (int) $pid;

            if ($pid > 0) {
                if ($port > 0) {
                    SilerGraphQL\subscriptions_at("ws://127.0.0.1:{$port}/");
                } else {
                    SilerGraphQL\subscriptions_at("ws://127.0.0.1:3000/");
                }
            }
        }
    }
}
