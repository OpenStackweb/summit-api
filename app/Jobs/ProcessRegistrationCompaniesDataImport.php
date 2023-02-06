<?php namespace App\Jobs;
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
 * Class ProcessRegistrationCompaniesDataImport
 * @package App\Jobs
 */
class ProcessRegistrationCompaniesDataImport implements ShouldQueue
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
     * ProcessRegistrationCompaniesDataImport constructor.
     * @param int $summit_id
     * @param string $filename
     */
    public function __construct(int $summit_id, string $filename)
    {
        Log::debug(sprintf("ProcessRegistrationCompaniesDataImport::__construct"));
        $this->summit_id = $summit_id;
        $this->filename = $filename;
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
            Log::debug(sprintf("ProcessRegistrationCompaniesDataImport::handle summit %s filename %s", $this->summit_id, $this->filename));
            $service->processRegistrationCompaniesData($this->summit_id, $this->filename);
        } catch (ValidationException $ex) {
            Log::warning($ex);
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    /**
     * @param Exception $exception
     */
    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "ProcessRegistrationCompaniesDataImport::failed %s", $exception->getMessage()));
    }
}