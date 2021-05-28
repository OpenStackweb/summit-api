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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
/**
 * Class SynchPresentationActions
 * @package App\Jobs
 */
class SynchPresentationActions implements ShouldQueue
{
    public $tries = 2;

    public $timeout = PHP_INT_MAX;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $event_id;

    /**
     * SynchAllPresentationActions constructor.
     * @param int $event_id
     */
    public function __construct(int $event_id)
    {
        $this->event_id = $event_id;
    }

    /**
     * @param ISummitRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle(
        ISummitEventRepository $repository,
        ITransactionService $tx_service
    )
    {
        Log::debug(sprintf("SynchPresentationActions::handle event id %s", $this->event_id));
        $tx_service->transaction(function() use($repository){
            $event = $repository->getById($this->event_id);

            if(is_null($event))
                throw new EntityNotFoundException(sprintf("Event %s ", $this->event_id));

            if(!$event instanceof Presentation){
                return;
            }

            $event->getSummit()->synchAllPresentationActions();
        });
    }
}