<?php namespace App\Listeners;
/*
 * Copyright 2024 OpenStack Foundation
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
use App\Events\SummitAttendeeCheckInStateUpdated;
use App\Services\Model\IAttendeeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
/**
 * Class ProcessSummitAttendeeCheckInStateUpdated
 * @package App\Listeners
 */
class ProcessSummitAttendeeCheckInStateUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 5;

    /**
     * @var IAttendeeService
     */
    private $service;
    /**
     * @param IAttendeeService $service
     */
    public function __construct(IAttendeeService $service)
    {
        Log::debug("ProcessSummitAttendeeCheckInStateUpdated::constructor");
        $this->service = $service;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\SummitAttendeeCheckInStateUpdated  $event
     * @return void
     */
    public function handle(SummitAttendeeCheckInStateUpdated $event)
    {
        Log::debug
        (
            sprintf
            (
                "ProcessSummitAttendeeCheckInStateUpdated::handle attendee %s", $event->getAttendeeId()
            )
        );

        $this->service->processAttendeeCheckStatusUpdate($event->getAttendeeId());
    }

    public function failed(SummitAttendeeCheckInStateUpdated $event, $exception)
    {
        Log::debug
        (
            sprintf
            (
                "ProcessSummitAttendeeCheckInStateUpdated::failed attendee %s", $event->getAttendeeId()
            )
        );
        Log::error($exception);
    }
}
