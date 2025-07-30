<?php namespace App\Models\Foundation\Summit\Events\RSVP;
/**
 * Copyright 2025 OpenStack Foundation
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
use models\summit\SummitAttendee;
use models\utils\SilverstripeBaseModel;

#[ORM\Table(name: 'RSVPInvitation')]
class RSVPInvitation extends SilverstripeBaseModel
{

    public const string Status_Pending = 'Pending';
    public const string Status_Accepted = 'Accepted';
    public const string Status_Rejected = 'Rejected';

    public const array AllowedStatus = [
        self::Status_Pending,
        self::Status_Accepted,
        self::Status_Rejected
    ];

    #[ORM\Column(name: 'Status', type: 'string')]
    protected string $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private string $hash;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ActionDate', type: 'datetime')]
    private \DateTime $action_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SentDate', type: 'datetime')]
    private \DateTime $sent_date;


    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'AttendeeID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: SummitAttendee::class)]
    private SummitAttendee $invitee;


}