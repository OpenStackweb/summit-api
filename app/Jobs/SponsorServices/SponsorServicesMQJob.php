<?php namespace App\Jobs\SponsorServices;

/**
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

use Illuminate\Support\Facades\Log;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class SponsorServicesMQJob extends BaseJob
{
    public int $tries = 3;

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        $routing_key = $this->getRabbitMQMessage()->getRoutingKey();

        switch ($routing_key) {
            case EventTypes::AUTH_USER_ADDED_TO_GROUP:
            case EventTypes::AUTH_USER_REMOVED_FROM_GROUP:
                $job = 'App\Jobs\SponsorServices\UpdateSponsorMemberGroupsMQJob@handle';
                break;
            case EventTypes::AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT:
                $job = 'App\Jobs\SponsorServices\AddSponsorMemberMQJob@handle';
                break;
            case EventTypes::AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT:
            case EventTypes::AUTH_USER_REMOVED_FROM_SUMMIT:
                $job = 'App\Jobs\SponsorServices\RemoveSponsorMemberMQJob@handle';
                break;
            default:
                Log::warning('Received an unknown routing key', ['routing_key' => $routing_key, 'message' => $this->getRawBody()]);
                return [];
        }
        return [
            'job' => $job,
            'data' => json_decode($this->getRawBody(), true)
        ];
    }
}
