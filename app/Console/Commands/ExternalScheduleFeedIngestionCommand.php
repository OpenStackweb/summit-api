<?php namespace App\Console\Commands;
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
use App\Services\Model\IScheduleIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
/**
 * Class ExternalScheduleFeedIngestionCommand
 * @package App\Console\Commands
 */
final class ExternalScheduleFeedIngestionCommand extends Command {

    /**
     * @var IScheduleIngestionService
     */
    private $service;

    /**
     * ExternalScheduleFeedIngestionCommand constructor.
     * @param IScheduleIngestionService $service
     */
    public function __construct(IScheduleIngestionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:external-schedule-feed-ingestion-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process External Schedule Feed for summits';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:external-schedule-feed-ingestion-process';


    public function handle()
    {
        $this->info("starting summits external ingestion");
        $start  = time();
        $this->service->ingestAllSummits();
        $end   = time();
        $delta = $end - $start;
        $this->info(sprintf("execution call %s seconds", $delta));
        log::info(sprintf("execution call %s seconds", $delta));
    }
}