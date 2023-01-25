<?php namespace App\Jobs\Emails\PresentationSubmissions;
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

use App\Services\Model\ISummitSubmissionInvitationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use utils\FilterParser;

/**
 * Class ProcessSubmissionsInvitationsJob
 * @package App\Jobs
 */
final class ProcessSubmissionsInvitationsJob
    implements ShouldQueue
{
    public $tries = 1;

    // no timeout
    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $summit_id;

    private $payload;

    private $filter;

    /**
     * ProcessRegistrationInvitationsJob constructor.
     * @param Summit $summit
     * @param array $payload
     * @param $filter
     */
    public function __construct(Summit $summit, array $payload, $filter)
    {
        $this->summit_id = $summit->getId();
        $this->payload = $payload;
        $this->filter = $filter;
    }

    /**
     * @param ISummitSubmissionInvitationService $service
     * @throws \utils\FilterParserException
     */
    public function handle(ISummitSubmissionInvitationService $service)
    {

        Log::debug(sprintf("ProcessSubmissionsInvitationsJob::handle summit id %s", $this->summit_id));

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
            'is_sent' => ['=='],
            'email' => ['@@', '=@', '=='],
            'first_name' => ['@@', '=@', '=='],
            'last_name' => ['@@', '=@', '=='],
            'tags' => ['@@', '=@', '=='],
            'tags_id' => ['=='],
        ]) : null;

        $service->send($this->summit_id, $this->payload, $filter);

        Log::debug(sprintf("ProcessSubmissionsInvitationsJob::handle summit id %s has finished", $this->summit_id));

    }
}