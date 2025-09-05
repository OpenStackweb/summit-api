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
 * Class RemoveSponsorMemberMQJob
 * @package App\Jobs\SponsorServices
 */
final class RemoveSponsorMemberMQJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public ISponsorUserSyncService $service;

    /**
     * RemoveSponsorMemberMQJob constructor.
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
            Log::debug("RemoveSponsorMemberMQJob::handle payload {$json}");

            $data = $payload['data'];

            $summit_id = intval($data['summit_id']);
            $user_external_id = intval($data['user_external_id']);
            $sponsor_id = null;
            if($event_type === EventTypes::AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT) {
                if(!isset($data['sponsor_id'])) {
                    throw new ValidationException('sponsor_id is required when event_type is auth_user_removed_from_sponsor_and_summit');
                }
                $sponsor_id = intval($data['sponsor_id']);
            }
            $this->service->removeSponsorUser($summit_id, $user_external_id, $sponsor_id);
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
        Log::error("RemoveSponsorMemberMQJob::failed {$exception->getMessage()}");
    }
}