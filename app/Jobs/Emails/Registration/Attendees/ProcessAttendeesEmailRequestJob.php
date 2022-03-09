<?php namespace App\Jobs\Emails;
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
use App\Services\Model\IAttendeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use utils\FilterParser;
/**
 * Class ProcessAttendeesEmailRequestJob
 * @package App\Jobs\Emails
 */
final class ProcessAttendeesEmailRequestJob implements ShouldQueue
{
    public $timeout = 0;

    public $tries = 1;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $summit_id;

    private $payload;

    private $filter;

    /**
     * ProcessAttendeesEmailRequestJob constructor.
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

    public function handle(IAttendeeService $service){
        Log::debug
        (
            sprintf
            (
                "ProcessAttendeesEmailRequestJob::handle summit id %s payload %s",
                $this->summit_id,
                json_encode($this->payload)
            )
        );

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
            'first_name'           => ['=@', '=='],
            'last_name'            => ['=@', '=='],
            'full_name'            => ['=@', '=='],
            'company'              => ['=@', '=='],
            'email'                => ['=@', '=='],
            'external_order_id'    => ['=@', '=='],
            'external_attendee_id' => ['=@', '=='],
            'member_id'            => ['==', '>'],
            'ticket_type'          => ['=@', '=='],
            'badge_type'           => ['=@', '=='],
            'status'               => ['=@', '=='],
            'has_tickets'          => ['=='],
            'has_member'           => ['=='],
            'tickets_count'        => ['==','>','<','>='.'<='],
            'has_virtual_checkin'  => ['=='],
        ]) : null;

        $service->send($this->summit_id, $this->payload, $filter);
    }

}
