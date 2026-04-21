<?php namespace App\Console\Commands;
/**
 * Copyright 2026 OpenStack Foundation
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
use Exception;
use services\model\ISummitService;

/**
 * Class ProcessPendingMediaUploadsCommand
 * @package App\Console\Commands
 */
class ProcessPendingMediaUploadsCommand extends Command
{
    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @param ISummitService $summit_service
     */
    public function __construct(ISummitService $summit_service)
    {
        parent::__construct();
        $this->summit_service = $summit_service;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:process-pending-media-uploads';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:process-pending-media-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending media uploads from the PendingMediaUpload table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->info("ProcessPendingMediaUploadsCommand::handle starting");
            $start = time();

            $stats = $this->summit_service->processPendingMediaUploads();

            $end = time();
            $delta = $end - $start;

            $this->info(sprintf(
                "ProcessPendingMediaUploadsCommand::handle completed in %s seconds - processed: %s, errors: %s",
                $delta,
                $stats['processed'],
                $stats['errors']
            ));

        } catch (Exception $ex) {
            Log::warning($ex);
            $this->error($ex->getMessage());
        }
    }
}
