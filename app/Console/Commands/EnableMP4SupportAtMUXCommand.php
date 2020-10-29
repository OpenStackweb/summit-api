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
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
/**
 * Class EnableMP4SupportAtMUXCommand
 * @package App\Console\Commands
 */
class EnableMP4SupportAtMUXCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:enable-mp4-support-mux';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:enable-mp4-support-mux {event_id}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable MP4 Support at Mux';

    /**
     * @param IPresentationVideoMediaUploadProcessor $service
     */
    public function handle(IPresentationVideoMediaUploadProcessor $service)
    {
        $event_id = $this->argument('event_id');

        if(empty($event_id))
            throw new \InvalidArgumentException("event_id is required");

        $service->enableMP4Support(intval($event_id));
    }
}