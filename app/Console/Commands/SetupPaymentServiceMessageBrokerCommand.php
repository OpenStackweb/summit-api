<?php namespace App\Console\Commands;
/*
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use App\Jobs\Payments\EventTypes;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class SetupSponsorServiceMessageBrokerCommand
 * @package App\Console\Commands
 */
final class SetupPaymentServiceMessageBrokerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = "setup_payment_service_message_broker";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "mq:setup_payment_service_message_broker {exchange_name} {exchange_type}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set up Payment Service rabbitmq exchange, queue and bindings";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $host_settings_path = "queue.connections.message_broker.hosts.0";
        $queue_settings_path = "queue.connections.payments_sync_consumer";
        $host_settings = Config::get($host_settings_path);

        $exchange_name = $this->argument('exchange_name');

        if (empty($exchange_name))
            throw new \InvalidArgumentException("exchange_name is required");

        $exchange_type = $this->argument('exchange_type');

        if (empty($exchange_type))
            throw new \InvalidArgumentException("exchange_type is required");

        if (!$host_settings) {
            throw new \InvalidArgumentException("Host setting not found at {$host_settings_path}");
        }
        $queue_settings = Config::get($queue_settings_path);

        $host = $host_settings['host'];
        $port = $host_settings['port'];
        $user = $host_settings['user'];
        $password = $host_settings['password'];
        $vhost = $host_settings['vhost'];

        $queue = $queue_settings['queue'];

        $routingKeys = [
            EventTypes::PAYMENT_PROFILE_UPDATED,
            EventTypes::PAYMENT_PROFILE_CREATED,
            EventTypes::PAYMENT_PROFILE_DELETED,
        ];

        try {
            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();

            // Exchange
            $channel->exchange_declare($exchange_name, $exchange_type, false, true, false);

            // Queue
            $channel->queue_declare($queue, false, true, false, false);

            // Bindings
            foreach ($routingKeys as $key) {
                $channel->queue_bind($queue, $exchange_name, $key);
                echo "Binding created: $queue ← $exchange_name ($key)\n";
            }

            $channel->close();
            $connection->close();
            echo "Done.\n";

        } catch (Exception $ex) {
            echo "Error: " . $ex->getMessage() . "\n";
            Log::error($ex);
            exit(1);
        }
    }
}
