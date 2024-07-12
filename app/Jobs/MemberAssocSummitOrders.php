<?php namespace App\Jobs;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Services\Model\IMemberService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Class MemberAssocSummitOrders
 * @package App\Jobs
 */
class MemberAssocSummitOrders implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 5;
  /**
   * @var int
   */
  private $member_id;

  /**
   * NewMemberAssocSummitOrders constructor.
   * @param int $member_id
   */
  public function __construct(int $member_id) {
    $this->member_id = $member_id;
  }

  /**
   * @param IMemberService $service
   */
  public function handle(IMemberService $service) {
    try {
      $service->assocSummitOrders($this->member_id);
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }
}
