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

use App\Events\SponsorServices\SponsorDomainEvents;
use App\Events\SponsorServices\SummitDomainEvents;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class SetupSponsorServiceMessageBrokerCommand
 * @package App\Console\Commands
 */
final class SetupSponsorServiceMessageBrokerCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = "setup_domain_events_message_broker";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "mq:setup_domain_events_message_broker";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set up Sponsor Services rabbitmq exchange, queue and bindings";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $host_settings_path = "queue.connections.domain_events_message_broker.hosts.0";
        $exchange_settings_path = "queue.connections.domain_events_message_broker.options.exchange";

        $host_settings = Config::get($host_settings_path);

        if (!$host_settings) {
            throw new \InvalidArgumentException("Host setting not found at {$host_settings_path}");
        }

        $exchange_settings = Config::get($exchange_settings_path);

        if (!$exchange_settings) {
            throw new \InvalidArgumentException("Exchange setting not found at {$exchange_settings_path}");
        }

        $host     = $host_settings['host'];
        $port     = $host_settings['port'];
        $user     = $host_settings['user'];
        $password = $host_settings['password'];
        $vhost    = $host_settings['vhost'];

        $exchange_name = $exchange_settings['name'];
        $exchange_type = $exchange_settings['type'];
        $queue = 'summit-api-sponsor-services-queue';

        $routingKeys = [
            SummitDomainEvents::SummitCreated,
            SummitDomainEvents::SummitUpdated,
            SummitDomainEvents::SummitDeleted,
            SponsorDomainEvents::SponsorCreated,
            SponsorDomainEvents::SponsorUpdated,
            SponsorDomainEvents::SponsorDeleted,
            SponsorDomainEvents::SponsorshipCreated,
            SponsorDomainEvents::SponsorshipUpdated,
            SponsorDomainEvents::SponsorshipRemoved,
            SponsorDomainEvents::SponsorshipAddOnCreated,
            SponsorDomainEvents::SponsorshipAddOnUpdated,
            SponsorDomainEvents::SponsorshipAddOnRemoved,
        ];

        try {
            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel    = $connection->channel();

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