<?php
namespace App\Jobs;
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

use App\Services\Model\IAttendeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessSummitAttendeeCheckInStateUpdated
 * @package App\Jobs
 */
class ProcessSummitAttendeeCheckInStateUpdated implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 5;

  /**
   * @var
   */
  private $attendee_id;

  public function __construct(int $attendee_id) {
    Log::debug(
      sprintf("ProcessSummitAttendeeCheckInStateUpdated::__construct attendee %s", $attendee_id),
    );
    $this->attendee_id = $attendee_id;
  }

  public function handle(IAttendeeService $service) {
    try {
      Log::debug(
        sprintf("ProcessSummitAttendeeCheckInStateUpdated::handle attendee %s", $this->attendee_id),
      );
      $service->processAttendeeCheckStatusUpdate($this->attendee_id);
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }

  public function failed(\Throwable $exception) {
    Log::error(
      sprintf("ProcessSummitAttendeeCheckInStateUpdated::failed %s", $exception->getMessage()),
    );
  }
}
