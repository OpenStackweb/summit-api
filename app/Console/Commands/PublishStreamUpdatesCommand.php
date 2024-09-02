<?php namespace App\Console\Commands;
/*
 * Copyright 2024 OpenStack Foundation
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
use services\model\ISummitService;

/**
 * Class PublishStreamUpdatesCommand
 * @package App\Console\Commands
 */
class PublishStreamUpdatesCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:publish-stream-updates';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:publish-stream-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish stream updates';


    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * SummitEventSetAvgRateProcessor constructor.
     * @param ISummitService $summit_service
     */
    public function __construct(ISummitService $summit_service)
    {
        parent::__construct();
        $this->summit_service = $summit_service;
    }

    public function handle()
    {
        try {
            $this->summit_service->publishStreamUpdatesStartInXMinutes(10);
        }
        catch (\Exception $ex) {
            Log::warning($ex);
        }
    }
}