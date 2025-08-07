<?php

namespace App\Models\Foundation\Summit\Events\RSVP\Repositories;

use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use models\summit\SummitEvent;
use models\utils\IBaseRepository;

interface IRSVPInvitationRepository extends IBaseRepository
{
    /**
     * @param SummitEvent $summit_event
     * @return array|int[]
     */
    public function getAllIdsNonAcceptedPerSummitEvent(SummitEvent $summit_event):array;

    /**
     * @param string $hash
     * @param SummitEvent $summit_event
     * @return RSVPInvitation|null
     */
    public function getByHashAndSummitEvent(string $hash, SummitEvent $summit_event):?RSVPInvitation;

    /**
     * @param string $hash
     * @return RSVPInvitation|null
     */
    public function getByHashExclusiveLock(string $hash):?RSVPInvitation;
}