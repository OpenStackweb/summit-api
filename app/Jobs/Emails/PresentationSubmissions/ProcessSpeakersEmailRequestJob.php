<?php namespace App\Jobs\Emails;
/**
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
use models\summit\ISummitRepository;
use models\summit\Summit;
use services\model\ISpeakerService;
use utils\FilterParser;
/**
 * Class ProcessSpeakersEmailRequestJob
 * @package App\Jobs\Emails
 */
final class ProcessSpeakersEmailRequestJob implements ShouldQueue
{
    public $timeout = 0;

    public $tries = 1;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $summit_id;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var mixed
     */
    private $filter;

    /**
     * ProcessSpeakersEmailRequestJob constructor.
     * @param int $summit_id
     * @param array $payload
     * @param $filter
     */
    public function __construct(int $summit_id, array $payload, $filter)
    {
        $this->summit_id = $summit_id;
        $this->payload = $payload;
        $this->filter = $filter;
    }

    /**
     * @param ISummitRepository $summit_repository
     * @param ISpeakerService $service
     * @throws \utils\FilterParserException
     */
    public function handle
    (
        ISpeakerService $service
    ){
        Log::debug
        (
            sprintf
            (
                "ProcessSpeakersEmailRequestJob::handle summit id %s payload %s",
                $this->summit_id,
                json_encode($this->payload)
            )
        );

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
            'first_name' => ['=@', '@@', '=='],
            'last_name' => ['=@', '@@', '=='],
            'email' => ['=@', '@@', '=='],
            'id' => ['=='],
            'full_name' => ['=@', '@@', '=='],
            'has_accepted_presentations' => ['=='],
            'has_alternate_presentations' => ['=='],
            'has_rejected_presentations' => ['=='],
            'presentations_track_id' => ['=='],
            'presentations_selection_plan_id' =>  ['=='],
            'presentations_type_id'           =>  ['=='],
            'presentations_title'             => ['=@', '@@', '=='],
            'presentations_abstract'          => ['=@', '@@', '=='],
            'presentations_submitter_full_name' => ['=@', '@@', '=='],
            'presentations_submitter_email' => ['=@', '@@', '=='],
        ]) : null;

        $service->sendEmails($this->summit_id, $this->payload, $filter);
    }
}
