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

    /**
     * @param array $payload
     * @param string $event_type
     * @throws \Exception
     */
    public function handle(array $payload, string $event_type): void
    {
        try {
            $sponsor_services_publisher = RabbitPublisherFactory::make('sponsor_services_sync_message_broker');
            $sponsor_services_publisher->publish($payload, $event_type);
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param array $data
     * @param Throwable $exception
     */
    public function failed(array $data, Throwable $exception): void
    {
        Log::error("PublishSponsorServiceDomainEventsJob::failed {$exception->getMessage()}");
    }
}