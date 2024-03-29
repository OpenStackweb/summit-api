<?php namespace App\Jobs;
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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use services\model\ISummitService;
use Exception;
/**
 * Class ProcessEventDataImport
 * @package App\Jobs
 */
class ProcessEventDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    // no timeout
    public $timeout = 0;

    /*
     * @var int
     */
    private $summit_id;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var boolean
     */
    private $send_speaker_email;

    /**
     * ProcessEventDataImport constructor.
     * @param int $summit_id
     * @param string $filename
     * @param array $payload
     */
    public function __construct(int $summit_id, string $filename, array $payload)
    {
        Log::debug(sprintf("ProcessEventDataImport::__construct"));
        $this->summit_id = $summit_id;
        $this->filename = $filename;
        $this->send_speaker_email = boolval($payload['send_speaker_email']);
    }

    /**
     * @param ISummitService $service
     */
    public function handle
    (
        ISummitService $service
    )
    {
        try {
            Log::debug(sprintf("ProcessEventDataImport::handle summit %s filename %s send_speaker_email %s", $this->summit_id, $this->filename, $this->send_speaker_email));
            $service->processEventData($this->summit_id, $this->filename, $this->send_speaker_email);
        } catch (ValidationException $ex) {
            Log::warning($ex);
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "ProcessEventDataImport::failed %s", $exception->getMessage()));
    }
}