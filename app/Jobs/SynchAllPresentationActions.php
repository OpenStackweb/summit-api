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
use models\summit\ISummitRepository;
/**
 * Class SynchAllPresentationActions
 * @package App\Jobs
 */
class SynchAllPresentationActions implements ShouldQueue
{
    public $tries = 2;

    // no timeout
    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $summit_id;

    /**
     * SynchAllPresentationActions constructor.
     * @param int $summit_id
     */
    public function __construct(int $summit_id)
    {
        $this->summit_id = $summit_id;
    }

    /**
     * @param ISummitRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle(
        ISummitRepository $repository,
        ITransactionService $tx_service
    )
    {
        Log::debug(sprintf("SynchAllPresentationActions::handle summit %s", $this->summit_id));
        $tx_service->transaction(function() use($repository){
            $summit = $repository->getById($this->summit_id);
            if(is_null($summit))
                throw new EntityNotFoundException(sprintf("Summit %s not found", $this->summit_id));

            $summit->synchAllPresentationActions();
        });
    }
}