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

use App\Services\Model\ISummitOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class CopyInvitationTagsToAttendees
 * @package App\Jobs
 */
class CopyInvitationTagsToAttendees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * @var int
     */
    public $summit_id;

    /*
     * @var int
     */
    public $invitation_id;

    /*
     * @var int
     */
    public $attendee_id;

    /**
     * @param int $summit_id
     * @param int $invitation_id
     * @param int $attendee_id
     */
    public function __construct(int $summit_id, int $invitation_id, int $attendee_id)
    {
        $this->summit_id = $summit_id;
        $this->invitation_id = $invitation_id;
        $this->attendee_id = $attendee_id;
    }

    public function handle(ISummitOrderService $service)
    {
        try {
            Log::debug(
                "CopyInvitationTagsToAttendees::handle summit id {$this->summit_id}, invitation id {$this->invitation_id}, attendee id {$this->attendee_id}");
            $service->copyInvitationTagsToAttendee($this->summit_id, $this->invitation_id, $this->attendee_id);
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}