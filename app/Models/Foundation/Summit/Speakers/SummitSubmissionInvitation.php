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
use Doctrine\Common\Collections\ArrayCollection;
use models\main\Tag;
use models\summit\PresentationSpeaker;
use models\summit\SummitOwned;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;
/**
 * /**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitSubmissionInvitation')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitSubmissionInvitationRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'submission_invitations')])]
class SummitSubmissionInvitation extends SilverstripeBaseModel
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSpeakerId' => 'speaker',
    ];

    protected $hasPropertyMappings = [
        'hasSpeaker' => 'speaker',
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'FirstName', type: 'string')]
    private $first_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LastName', type: 'string')]
    private $last_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Email', type: 'string')]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'OTP', type: 'string')]
    private $otp;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SentDate', type: 'datetime')]
    private $sent_date;

    #[ORM\JoinTable(name: 'SummitSubmissionInvitation_Tags')]
    #[ORM\JoinColumn(name: 'SummitSubmissionInvitationID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'TagID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Tag::class, cascade: ['persist'], inversedBy: 'events', fetch: 'EXTRA_LAZY')]
    private $tags;

    /**
     * @var PresentationSpeaker
     */
    #[ORM\JoinColumn(name: 'SpeakerID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \models\summit\PresentationSpeaker::class)]
    private $speaker;

    public function __construct()
    {
        parent::__construct();
        $this->tags = new ArrayCollection();
        $this->speaker = null;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }

    /**
     * @return string
     */
    public function getOtp(): ?string
    {
        return $this->otp;
    }

    /**
     * @param string $otp
     */
    public function setOtp(string $otp): void
    {
        $this->otp = $otp;
    }


    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) return;
        $this->tags->add($tag);
    }

    public function clearTags()
    {
        $this->tags->clear();
    }

    /**
     * @return \DateTime
     */
    public function getSentDate(): ?\DateTime
    {
        return $this->sent_date;
    }

    /**
     * @return bool
     */
    public function isSent():bool{
        return !is_null($this->sent_date);
    }

    public function markAsSent():void{
        $this->sent_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }
    /**
     * @return PresentationSpeaker
     */
    public function getSpeaker(): ?PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker(?PresentationSpeaker $speaker): void
    {
        $this->speaker = $speaker;
    }
}