<?php namespace App\Console\Commands;
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitRepository;
use services\model\ISummitService;

/**
 * Class PresentationMediaUploadsRegenerateTemporalLinks
 * @package App\Console\Commands
 */
final class PresentationMediaUploadsRegenerateTemporalLinks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:presentations-regenerate-media-uploads-temporal-public-urls';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:presentations-regenerate-media-uploads-temporal-public-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates Public Temporal Media uploads Urls';

    /**
     * @var ISummitService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $repository;

    /**
     * @param ISummitService $service
     * @param ISummitRepository $repository
     */
    public function __construct(ISummitService $service, ISummitRepository $repository)
    {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
    }

    public function handle()
    {
        Log::debug(sprintf("PresentationMediaUploadsRegenerateTemporalLinks::handle start"));
        foreach ($this->repository->getNotEnded() as $summit) {
            Log::debug(sprintf("PresentationMediaUploadsRegenerateTemporalLinks::handle processing summit %s", $summit->getId()));
            try {
                $this->service->regenerateTemporalUrlsForMediaUploads($summit->getId());
            }
            catch (\Exception $ex){
                Log::error($ex);
            }
        }
        Log::debug(sprintf("PresentationMediaUploadsRegenerateTemporalLinks::handle finish"));
    }
}