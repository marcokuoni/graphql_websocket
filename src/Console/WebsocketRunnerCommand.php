<?php
namespace Concrete5GraphqlWebsocket\Console;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Console\Command;
use Concrete5GraphqlWebsocket\SchemaBuilder;
use Siler\GraphQL as SilerGraphQL;
use Siler\SubscriptionManager;

use function Siler\Swoole\graphql_subscriptions;
use function Siler\Swoole\json;
use Swoole\Http\Request;

class WebsocketRunnerCommand extends Command
{
    protected $description = 'Start a websocket server';

    protected $signature = <<<'EOT'
gws:start
    {port : the websocket port number}
EOT
    ;

    public function handle(Repository $config)
    {
        $port = (int) $this->input->getArgument('port');
        if ($port < 1 || $port > 65535) {
            $this->output->error('The port number must be an integer between 1 and 65535');

            return 1;
        }
        $serverIP = $config->get('concrete5_graphql_websocket::websocket.server_ip');
        $this->output->write("Checking availability of port {$port} on {$serverIP}... ");
        $connection = @fsockopen($serverIP, $port);
        if (is_resource($connection)) {
            fclose($connection);
            $this->output->error("server already exists on this port!\n");

            return 2;
        }
        $this->output->writeln("good.\n");
        $this->output->write('Retrieving schema... ');
        $schema = SchemaBuilder::get();
        if (!$schema) {
            $this->output->error("schema not found!\n");

            return 3;
        }
        $this->output->writeln("found.\n");
        $pid = getmypid();
        if ($pid === false) {
            $this->output->error("Failed to get the process ID!\n");
        }
        $this->output->write("Starting running server with PID {$pid}\n\n\n");
        $config->save('concrete5_graphql_websocket::websocket.servers.' . $port, $pid);
        // SilerGraphQL\subscriptions($schema, [], $serverIP, $port)->run();

        $manager = SilerGraphQL\subscriptions_manager($schema, [], [], []);
        $server = graphql_subscriptions($manager, $port, $serverIP);
        $server->start();
    }
}
