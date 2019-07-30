<?php namespace models\summit;
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Support\Facades\Event;
use App\Events\RequestedSummitAttendeeTicketRefund;
use App\Events\SummitAttendeeTicketRefundAccepted;
use App\Models\Foundation\Summit\AllowedCurrencies;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeTicketFormerHash")
 * Class SummitAttendeeTicketFormerHash
 * @package models\summit
 */
class SummitAttendeeTicketFormerHash extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Hash", type="string")
     * @var string
     */
    private $hash;


    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendeeTicket", inversedBy="former_hashes")
     * @ORM\JoinColumn(name="SummitAttendeeTicketID", referencedColumnName="ID")
     * @var SummitAttendeeTicket
     */
    protected $ticket;

    /**
     * SummitAttendeeTicketFormerHash constructor.
     * @param string $hash
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(string $hash, SummitAttendeeTicket $ticket)
    {
        parent::__construct();
        $this->hash = $hash;
        $this->ticket = $ticket;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return SummitAttendeeTicket
     */
    public function getTicket(): SummitAttendeeTicket
    {
        return $this->ticket;
    }
}