<?php namespace App\Jobs;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessScheduleEntityLifeCycleEvent
 * @package App\Jobs
 */
class ProcessScheduleEntityLifeCycleEvent implements ShouldQueue
{
    public $tries = 1;

    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $entity_operator;

    /**
     * @var int
     */
    public $summit_id;

    /**
     * @var int
     */
    public $entity_id;

    /**
     * @var string
     */
    public $entity_type;

    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     */
    public function __construct(string $entity_operator, int $summit_id, int $entity_id, string $entity_type)
    {
        $this->entity_operator = $entity_operator;
        $this->summit_id = $summit_id;
        $this->entity_id = $entity_id;
        $this->entity_type = $entity_type;

        Log::debug
        (
            sprintf
            (
                "ProcessScheduleEntityLifeCycleEvent::ProcessScheduleEntityLifeCycleEvent %s %s %s %s",
                $entity_operator,
                $summit_id,
                $entity_id,
                $entity_type
            )
        );
    }

    /**
     * @param IProcessScheduleEntityLifeCycleEventService $service
     */
    public function handle(IProcessScheduleEntityLifeCycleEventService $service){

        Log::debug
        (
            sprintf
            (
                "ProcessScheduleEntityLifeCycleEvent::process %s %s %s %s",
                $this->entity_operator,
                $this->summit_id,
                $this->entity_id,
                $this->entity_type
            )
        );

        $service->process
        (
            $this->entity_operator,
            $this->summit_id,
            $this->entity_id,
            $this->entity_type
        );
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}