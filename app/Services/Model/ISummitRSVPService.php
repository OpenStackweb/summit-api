<?php

namespace App\Services\Model;

use models\main\Member;
use models\summit\RSVP;
use models\summit\Summit;

interface ISummitRSVPService
{
    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $payload
     * @return RSVP
     * @throws \Exception
     */
    public function addRSVP(Summit $summit, Member $member, int $event_id, array $payload = []): RSVP;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws Exception
     */
    public function unRSVPEvent(Summit $summit, Member $member, int $event_id):void;
}