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
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeTicketFormerHash')]
#[ORM\Entity]
class SummitAttendeeTicketFormerHash extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private $hash;


    /**
     * @var SummitAttendeeTicket
     */
    #[ORM\JoinColumn(name: 'SummitAttendeeTicketID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendeeTicket::class, inversedBy: 'former_hashes')]
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