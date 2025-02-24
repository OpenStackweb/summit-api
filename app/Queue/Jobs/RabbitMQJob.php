<?php namespace App\Queue\Jobs;
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
 * Class RabbitMQJob
 * @package App\Jobs
 */
final class RabbitMQJob extends BaseJob
{

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

            Log::debug("RabbitMQJob::handle payload {$json} from queue {$this->queue}");

            if ($this->queue == 'ADD_USER_TO_SPONSOR_QUEUE') {
                $sponsor_user_sync_service = App::make(ISponsorUserSyncService::class);
                $sponsor_user_sync_service->addSponsorUser($payload['data']);
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        } finally {
            $this->delete();
        }
    }
}