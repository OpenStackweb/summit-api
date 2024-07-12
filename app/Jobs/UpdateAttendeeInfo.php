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

use App\Services\Model\IAttendeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateAttendeeInfo
 * @package App\Jobs
 */
class UpdateAttendeeInfo implements ShouldQueue {
  public $tries = 2;

  // no timeout
  public $timeout = 0;

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @var int
   */
  private $member_id;

  /**
   * NewMember constructor.
   * @param int $member_id
   */
  public function __construct(int $member_id) {
    $this->member_id = $member_id;
  }

  /**
   * @param IAttendeeService $service
   */
  public function handle(IAttendeeService $service) {
    Log::debug(sprintf("UpdateAttendeeInfo::handle member_id %s", $this->member_id));
    $service->updateAttendeesByMemberId($this->member_id);
  }
}
