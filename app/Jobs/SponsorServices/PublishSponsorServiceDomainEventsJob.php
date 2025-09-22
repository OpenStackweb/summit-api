<?php namespace App\Jobs\SponsorServices;
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

use App\Services\Model\Imp\Factories\RabbitPublisherFactory;
use App\Services\Utils\IPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class PublishSponsorServiceDomainEventsJob
 * @package App\Jobs\SponsorServices
 */
final class PublishSponsorServiceDomainEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    private $event_type;

    public function __construct(array $payload, string $event_type){
        $this->payload = $payload;
        $this->event_type = $event_type;
        Log::debug(sprintf("PublishSponsorServiceDomainEventsJob::__construct payload %s event_type %s ", json_encode($payload), $event_type));
    }

    public function handle(): void
    {
        try {
            Log::debug(sprintf("PublishSponsorServiceDomainEventsJob::handle payload %s event_type %s", json_encode($this->payload), $this->event_type));
            $domain_event_publisher_service = RabbitPublisherFactory::make('domain_events_message_broker');
            $domain_event_publisher_service->publish($this->payload, $this->event_type);
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param Throwable $exception
     */
    public function failed(Throwable $exception): void
    {
        Log::error("PublishSponsorServiceDomainEventsJob::failed {$exception->getMessage()}");
    }
}