<?php
namespace App\Jobs;
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

use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;

/**
 * Class ProcessSelectionPlanAllowedMemberData
 * @package App\Jobs
 */
class ProcessSelectionPlanAllowedMemberData implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 2;

  // no timeout
  public $timeout = 0;

  /**
   * @var int
   */
  private $summit_id;

  /**
   * @var int
   */
  private $selection_plan_id;

  /**
   * @var string
   */
  private $filename;

  /**
   * @param int $summit_id
   * @param int $selection_plan_id
   * @param string $filename
   */
  public function __construct(int $summit_id, int $selection_plan_id, string $filename) {
    $this->summit_id = $summit_id;
    $this->selection_plan_id = $selection_plan_id;
    $this->filename = $filename;
  }

  /**
   * @param ISummitSelectionPlanService $service
   */
  public function handle(ISummitSelectionPlanService $service) {
    try {
      Log::debug(
        sprintf(
          "ProcessSelectionPlanAllowedMemberData::handle summit %s selection plan %s filename %s",
          $this->summit_id,
          $this->selection_plan_id,
          $this->filename,
        ),
      );
      $service->processAllowedMemberData(
        $this->summit_id,
        $this->selection_plan_id,
        $this->filename,
      );
    } catch (ValidationException $ex) {
      Log::warning($ex);
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }
}
