<?php namespace App\Services\Model\Imp\Traits;
/**
 * Copyright 2026 OpenStack Foundation
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

use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;

/**
 * A ticket fetched via an exclusive-lock repository call (getByIdExclusiveLock) does
 * not have its inverse-side badge association (SummitAttendeeTicket::$badge, mappedBy
 * 'ticket') populated by Doctrine, so $ticket->hasBadge()/getBadge() are unreliable
 * immediately afterward. Classes using this trait must inject
 * ISummitAttendeeBadgeRepository as $this->badge_repository.
 */
trait ResolvesLockedTicketBadge
{
    /**
     * Looks the badge up independently by ticket number and re-attaches it to
     * $ticket so the in-memory object (and anything serialized from it) reflects
     * the real association.
     * @param SummitAttendeeTicket $ticket
     * @return SummitAttendeeBadge|null
     */
    private function resolveBadgeForLockedTicket(SummitAttendeeTicket $ticket): ?SummitAttendeeBadge
    {
        $badge = $this->badge_repository->getBadgeByTicketNumber($ticket->getNumber());
        if (!is_null($badge)) {
            $ticket->setBadge($badge);
        }
        return $badge;
    }
}
