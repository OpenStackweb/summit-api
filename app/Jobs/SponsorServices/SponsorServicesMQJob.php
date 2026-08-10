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
     * Seconds to wait before the 2nd and 3rd attempt, as the comma-separated
     * string Worker::calculateBackoff() explodes.
     *
     * These events race the IDP's own user-updated event, which is what refreshes
     * a member's groups locally. Retrying immediately loses that race every time;
     * for auth_user_added_to_sponsor_and_summit that is terminal, because no
     * companion group event follows to repair the state.
     */
    const RetryBackoff = '30,120';

    /**
     * Get the decoded body of the job.
     *
     * Note the maxTries/backoff keys: Job::maxTries() and Job::backoff() read the
     * PAYLOAD, not the properties of the handler class this payload names. Without
     * them the worker falls back to the command's own options, and the entry point
     * runs `doctrine:queue:work sponsor_users_sync_consumer` with no flags - so the
     * defaults of --tries=1 and --backoff=0 apply and a single failure is final.
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
            'data' => json_decode($this->getRawBody(), true),
            'maxTries' => $this->tries,
            'backoff' => self::RetryBackoff,
        ];
    }
}
