<?php namespace App\Jobs;
/*
 * Copyright 2023 OpenStack Foundation
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
use services\model\ISummitService;

/**
 * Class CreateMUXPlaybackRestrictionForSummit
 * @package App\Jobs
 */
final class CreateMUXPlaybackRestrictionForSummit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    /**
     * @var int
     */
    private $summit_id;

    /**
     * @param int $summit_id
     */
    public function __construct(int $summit_id)
    {
        $this->summit_id = $summit_id;
    }

    public function handle(ISummitService $service)
    {
        try {
            Log::debug("CreateMUXPlaybackRestrictionForSummit::handle summit id {$this->summit_id}");
            $service->generateMuxPlaybackRestriction($this->summit_id);
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::debug
        (
            sprintf
            (
                "CreateMUXPlaybackRestrictionForSummit::failed summit id %s error %s",
                $this->summit_id,
                $exception->getMessage()
            )
        );
        Log::error($exception);
    }
}