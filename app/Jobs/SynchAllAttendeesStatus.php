<?php namespace App\Jobs;
/**
 * Copyright 2021 OpenStack Foundation
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
use libs\utils\ITransactionService;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitRepository;

/**
 * Class SynchAllAttendeesStatus
 * @package App\Jobs
 */
final class SynchAllAttendeesStatus implements ShouldQueue {
  public $tries = 1;

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @var int
   */
  private $summit_id;

  /**
   * SynchAllAttendeesStatus constructor.
   * @param int $summit_id
   */
  public function __construct(int $summit_id) {
    $this->summit_id = $summit_id;
  }

  /**
   * @param IAttendeeService $service
   * @param ISummitRepository $repository
   * @param ISummitAttendeeRepository $attendeeRepository
   * @param ITransactionService $tx_service
   * @return void
   * @throws \Exception
   */
  public function handle(IAttendeeService $service) {
    Log::debug(sprintf("SynchAllAttendeesStatus::handle summit %s", $this->summit_id));
    $service->resynchAttendeesStatusBySummit($this->summit_id);
  }

  public function failed(\Throwable $exception) {
    Log::error($exception);
  }
}
