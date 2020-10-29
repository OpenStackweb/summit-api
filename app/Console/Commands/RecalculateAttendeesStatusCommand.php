<?php namespace App\Console\Commands;
/**
 * Copyright 2020 OpenStack Foundation
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
use Illuminate\Console\Command;
use models\summit\ISummitRepository;
use Exception;
use Illuminate\Support\Facades\Log;
/**
 * Class RecalculateAttendeesStatusCommand
 * @package App\Console\Commands
 */
class RecalculateAttendeesStatusCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:recalculate-attendees-status';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:recalculate-attendees-status {summit_id}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate Attendees Status';

    /**
     * @var ISummitRepository
     */
    private $repository;

    /**
     * @var IAttendeeService
     */
    private $service;

    public function __construct(
        ISummitRepository $repository,
        IAttendeeService $service
    )
    {
        parent::__construct();
        $this->repository    = $repository;
        $this->service       = $service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        try {

            $start   = time();
            $summit_id = $this->argument('summit_id');
            if(empty($summit_id))
                throw new \InvalidArgumentException("summit_id is required");
            $this->service->recalculateAttendeeStatus(intval($summit_id));
            $end   = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
        }
        catch (Exception $ex) {
            Log::error($ex);
        }
    }

}