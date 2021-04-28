<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use models\summit\ISummitRepository;
use services\model\ISummitService;
/**
 * Class SummitSyncAllPresentationActions
 * @package App\Console\Commands
 */
class SummitSyncAllPresentationActions extends Command {
    /**
     * @var ISummitService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $repository;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * SummitSyncAllPresentationActions constructor.
     * @param ISummitRepository $repository
     * @param ISummitService $service
     * @param ICacheService $cache_service
     */
    public function __construct(
        ISummitRepository $repository,
        ISummitService $service,
        ICacheService $cache_service
    )
    {
        parent::__construct();
        $this->repository    = $repository;
        $this->service       = $service;
        $this->cache_service = $cache_service;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:synch-presentation-actions';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:synch-presentation-actions';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synch All Summits Presention Actions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $summits = $this->repository->getCurrentAndFutureSummits();
        foreach($summits as $summit) {
            Log::debug(sprintf("SummitSyncAllPresentationActions::handle processing summit %s (%s)",  $summit->getName(), $summit->getId()));
            $this->info(sprintf("processing summit %s (%s)",  $summit->getName(), $summit->getId()));
            $summit->synchAllPresentationActions();
            $this->info(sprintf("regenerated presentation actions for summit id %s", $summit->getIdentifier()));
        }
    }

}