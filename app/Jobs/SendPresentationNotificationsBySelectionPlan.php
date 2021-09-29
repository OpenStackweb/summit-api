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

use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;

/**
 * Class SendPresentationNotificationsBySelectionPlan
 * @package App\Jobs
 */
class SendPresentationNotificationsBySelectionPlan
    implements ShouldQueue
{
    public $tries = 2;

    public $timeout = PHP_INT_MAX;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $summit_id;
    /**
     * @var int
     */
    private $selection_plan_id;

    /**
     * @var bool
     */
    private $dry_run;

    /**
     * SendPresentationNotificationsBySelectionPlan constructor.
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param bool $dry_run
     */
    public function __construct(Summit $summit, int $selection_plan_id, bool $dry_run)
    {
        $this->summit_id = $summit->getId();
        $this->selection_plan_id = $selection_plan_id;
        $this->dry_run = $dry_run;
    }

    public function handle(ISummitSelectionPlanService $service){

        Log::debug(sprintf("SendPresentationNotificationsBySelectionPlan::handle summit id %s selection plan %s dry run %b",
            $this->summit_id,
            $this->selection_plan_id,
            $this->dry_run
        ));

        $service->processPresentationNotifications($this->summit_id, $this->selection_plan_id, $this->dry_run);

    }

}