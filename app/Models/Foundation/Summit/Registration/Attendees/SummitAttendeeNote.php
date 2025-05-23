<?php namespace models\summit;
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

use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeNote')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitAttendeeNoteRepository::class)]
class SummitAttendeeNote extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getAuthorId' => 'author',
        'getOwnerId'  => 'owner',
        'getTicketId' => 'ticket',
    ];

    protected $hasPropertyMappings = [
        'hasAuthor' => 'author',
        'hasOwner'  => 'owner',
        'hasTicket' => 'ticket',
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'Content', type: 'string')]
    private $content;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'AuthorID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    private $author;

    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \SummitAttendee::class, inversedBy: 'notes')]
    private $owner;

    /**
     * @var SummitAttendeeTicket
     */
    #[ORM\JoinColumn(name: 'TicketID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \SummitAttendeeTicket::class, inversedBy: 'notes')]
    private $ticket;

    /**
     * @param string $content
     * @param SummitAttendee $owner
     */
    public function __construct(string $content, SummitAttendee $owner)
    {
        parent::__construct();
        $this->content = trim($content);
        $this->owner = $owner;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return Member|null
     */
    public function getAuthor(): ?Member
    {
        return $this->author;
    }

    /**
     * @param Member|null $author
     */
    public function setAuthor(?Member $author): void
    {
        $this->author = $author;
    }

    /**
     * @return SummitAttendee
     */
    public function getOwner(): SummitAttendee
    {
        return $this->owner;
    }

    /**
     * @param SummitAttendee $owner
     */
    public function setOwner(SummitAttendee $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function getTicket(): ?SummitAttendeeTicket
    {
        return $this->ticket;
    }

    /**
     * @param SummitAttendeeTicket|null $ticket
     */
    public function setTicket(?SummitAttendeeTicket $ticket): void
    {
        $this->ticket = $ticket;
    }
}