<?php namespace App\Jobs;
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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

/**
 * Class SyncSponsorMemberMQJob
 * @package App\Jobs
 */
final class SyncSponsorMemberMQJob extends BaseJob
{
    public $tries = 3;

    /**
     * Fire the job.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function fire()
    {
        try {
            $payload = $this->payload();
            $json = json_encode($payload);

            Log::debug("SyncSponsorMemberMQJob::fire payload {$json}");

            $data = $payload['data'];
            $summit_id = intval($data['summit_id']);
            $sponsor_id = intval($data['sponsor_id']);
            $user_external_id = intval($data['user_external_id']);

            $sponsor_user_sync_service = App::make(ISponsorUserSyncService::class);
            $sponsor_user_sync_service->addSponsorUser($summit_id, $sponsor_id, $user_external_id);
            $this->delete();
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param $e
     */
    public function failed($e)
    {
        Log::error("SyncSponsorMemberMQJob::failed {$e->getMessage()}");
    }
}