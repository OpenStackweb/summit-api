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
use App\Services\Apis\MuxCredentials;
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class VideoStreamUrlMUXProcessingForSummitJob
 * @package App\Jobs
 */
class VideoStreamUrlMUXProcessingForSummitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    /**
     * @var int
     */
    private $summit_id;

    /**
     * @var string|null
     */
    private $email_to;

    /**
     * @var string
     */
    private $mux_token_id;

    /**
     * @var string
     */
    private $mux_token_secret;


    /**
     * VideoStreamUrlMUXProcessingForSummitJob constructor.
     * @param int $summit_id
     * @param string $mux_token_id
     * @param string $mux_token_secret
     * @param string|null $email_to
     */
    public function __construct
    (
        int $summit_id,
        string $mux_token_id,
        string $mux_token_secret,
        ?string $email_to
    )
    {
        $this->summit_id = $summit_id;
        $this->email_to = $email_to;
        $this->mux_token_id = $mux_token_id;
        $this->mux_token_secret = $mux_token_secret;
    }

    /**
     * @param IPresentationVideoMediaUploadProcessor $service
     */
    public function handle(IPresentationVideoMediaUploadProcessor $service){

        Log::debug(sprintf("VideoStreamUrlMUXProcessingForSummitJob::handle summit %s", $this->summit_id));

        try {
            $service->processSummitEventsStreamURLs
            (
                $this->summit_id,
                new MuxCredentials(
                    $this->mux_token_id,
                    $this->mux_token_secret
                ),
                $this->email_to
            );
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}