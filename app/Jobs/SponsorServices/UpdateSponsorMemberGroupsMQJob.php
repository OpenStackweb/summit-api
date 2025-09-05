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

use App\Services\Model\ISponsorUserSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Throwable;

/**
 * Class UpdateSponsorMemberGroupsMQJob
 * @package App\Jobs\SponsorServices
 */
final class UpdateSponsorMemberGroupsMQJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public ISponsorUserSyncService $service;

    /**
     * UpdateSponsorMemberGroupsMQJob constructor.
     * @param ISponsorUserSyncService $service
     */
    public function __construct(ISponsorUserSyncService $service)
    {
        $this->service = $service;
    }

    /**
     * @param SponsorServicesMQJob $job
     * @throws BindingResolutionException
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(SponsorServicesMQJob $job): void
    {
        try {
            $event_type = $job->getRabbitMQMessage()->getRoutingKey();
            $payload = $job->payload();
            $json = json_encode($payload);
            Log::debug("UpdateSponsorMemberGroupsMQJob::handle payload {$json}");

            $data = $payload['data'];
            $user_external_id = intval($data['user_external_id']);
            $group_slug = $data['group_slug'];

            if ($event_type === EventTypes::AUTH_USER_ADDED_TO_GROUP) {
                $this->service->addSponsorUserToGroup($user_external_id, $group_slug);
            } else if ($event_type === EventTypes::AUTH_USER_REMOVED_FROM_GROUP) {
                $this->service->removeSponsorUserFromGroup($user_external_id, $group_slug);
            }
            $job->delete();
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
        Log::error("UpdateSponsorMemberGroupsMQJob::failed {$exception->getMessage()}");
    }
}