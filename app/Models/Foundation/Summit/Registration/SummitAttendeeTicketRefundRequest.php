<?php namespace models\summit;
/**
 * Copyright 2021 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;

/**
 * Class SummitAttendeeTicketRefundRequest
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeTicketRefundRequest")
 * @package models\summit
 */
class SummitAttendeeTicketRefundRequest extends SummitRefundRequest
{
    /**
     * @ORM\ManyToOne(targetEntity="SummitAttendeeTicket", inversedBy="refund_requests", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="TicketID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitAttendeeTicket
     */
    protected $ticket;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getRequestedById' => 'requested_by',
        'getActionById' => 'action_by',
        'getTicketId' => 'ticket',
    ];

    protected $hasPropertyMappings = [
        'hasRequestedBy' => 'requested_by',
        'hasActionBy' => 'action_by',
        'hasTicket' => 'ticket',
    ];

    /**
     * @return SummitAttendeeTicket
     */
    public function getTicket(): SummitAttendeeTicket
    {
        return $this->ticket;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function setTicket(SummitAttendeeTicket $ticket): void
    {
        $this->ticket = $ticket;
    }

    public function clearTicket():void{
        $this->ticket = null;
    }

    public function __construct(?Member $requested_by = null)
    {
        parent::__construct($requested_by);
    }

}