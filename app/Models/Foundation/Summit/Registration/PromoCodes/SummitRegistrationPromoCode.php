<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\main\Tag;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitRegistrationPromoCode')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitRegistrationPromoCodeRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['SummitRegistrationPromoCode' => 'SummitRegistrationPromoCode', 'SpeakerSummitRegistrationPromoCode' => 'SpeakerSummitRegistrationPromoCode', 'MemberSummitRegistrationPromoCode' => 'MemberSummitRegistrationPromoCode', 'SponsorSummitRegistrationPromoCode' => 'SponsorSummitRegistrationPromoCode', 'SummitRegistrationDiscountCode' => 'SummitRegistrationDiscountCode', 'MemberSummitRegistrationDiscountCode' => 'MemberSummitRegistrationDiscountCode', 'SpeakerSummitRegistrationDiscountCode' => 'SpeakerSummitRegistrationDiscountCode', 'SponsorSummitRegistrationDiscountCode' => 'SponsorSummitRegistrationDiscountCode', 'SpeakersRegistrationDiscountCode' => 'SpeakersRegistrationDiscountCode', 'SpeakersSummitRegistrationPromoCode' => 'SpeakersSummitRegistrationPromoCode', 'PrePaidSummitRegistrationPromoCode' => 'PrePaidSummitRegistrationPromoCode', 'PrePaidSummitRegistrationDiscountCode' => 'PrePaidSummitRegistrationDiscountCode'])] // Class SummitRegistrationPromoCode
class SummitRegistrationPromoCode extends SilverstripeBaseModel
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Code', type: 'string')]
    protected $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    protected $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalId', type: 'string')]
    protected $external_id;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'EmailSent', type: 'boolean')]
    protected $email_sent;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SentDate', type: 'datetime', nullable: false)]
    protected $sent_date;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'Redeemed', type: 'boolean')]
    protected $redeemed;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Source', type: 'string')]
    protected $source;

    /**
     * @var int
     */
    #[ORM\Column(name: 'QuantityAvailable', type: 'integer')]
    protected $quantity_available;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Notes', type: 'string')]
    protected $notes;

    /**
     * @var int
     */
    #[ORM\Column(name: 'QuantityUsed', type: 'integer')]
    protected $quantity_used;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ValidSinceDate', type: 'datetime')]
    protected $valid_since_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ValidUntilDate', type: 'datetime')]
    protected $valid_until_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AllowsToDelegate', type: 'boolean')]
    protected $allows_to_delegate;


    protected $allows_reassign_related_tickets;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AllowsReassign', type: 'boolean')]
    protected $allows_to_reassign;

    /**
     * @var Summit
     */
    #[ORM\JoinColumn(name: 'SummitID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Summit::class, inversedBy: 'promo_codes')]
    protected $summit;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'CreatorID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    protected $creator;

    /**
     * @var SummitBadgeFeatureType[]
     */
    #[ORM\JoinTable(name: 'SummitRegistrationPromoCode_BadgeFeatures')]
    #[ORM\JoinColumn(name: 'SummitRegistrationPromoCodeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitBadgeFeatureTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitBadgeFeatureType::class)]
    protected $badge_features;

    /**
     * @var SummitTicketType[]
     */
    #[ORM\JoinTable(name: 'SummitRegistrationPromoCode_AllowedTicketTypes')]
    #[ORM\JoinColumn(name: 'SummitRegistrationPromoCodeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitTicketTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitTicketType::class)]
    protected $allowed_ticket_types;

    /**
     * @var Tag[]
     */
    #[ORM\JoinTable(name: 'SummitRegistrationPromoCode_Tags')]
    #[ORM\JoinColumn(name: 'SummitRegistrationPromoCodeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'TagID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Tag::class, cascade: ['persist'], inversedBy: 'events', fetch: 'EXTRA_LAZY')]
    private $tags;

    /**
     * @var SummitAttendeeTicket[]
     */
    #[ORM\OneToMany(targetEntity: \SummitAttendeeTicket::class, mappedBy: 'promo_code', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $tickets;

    public function setSummit($summit)
    {
        $this->summit = $summit;
    }

    /**
     * @return Summit
     */
    public function getSummit()
    {
        return $this->summit;
    }

    public function clearSummit()
    {
        $this->summit = null;
    }

    /**
     * @return int
     */
    public function getSummitId()
    {
        try {
            return $this->summit->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     * @throws ValidationException
     */
    public function setCode(string $code): void
    {
        $new_code = strtoupper(trim($code));
        if (empty($new_code))
            throw new ValidationException("code can not be empty!");
        $this->code = $new_code;
    }

    /**
     * @param bool $email_sent
     * @param string|null $recipient
     * @return void
     */
    public function markSent(string $recipient = null)
    {
        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::markSent %s recipient %s",
                $this->code,
                $recipient
            )
        );
        $this->email_sent = true;
        $this->sent_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function isEmailSent():bool{
        return !is_null($this->sent_date);
    }

    /**
     * @return bool
     */
    public function isRedeemed():bool
    {
        return $this->redeemed;
    }

    /**
     * @param bool $redeemed
     */
    public function setRedeemed(bool $redeemed)
    {
        $this->redeemed = $redeemed;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return Member
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param Member $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function __construct()
    {
        parent::__construct();
        $this->email_sent = false;
        $this->redeemed = false;
        $this->quantity_available = 0;
        $this->quantity_used = 0;
        $this->valid_since_date = null;
        $this->valid_until_date = null;
        $this->allows_to_delegate = false;
        $this->allows_to_reassign = true;
        $this->sent_date = null;
        $this->badge_features = new ArrayCollection();
        $this->allowed_ticket_types = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->tickets = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function canUse(): bool
    {
        if (!$this->hasQuantityAvailable()) return false;
        return $this->isLive();
    }

    public function hasQuantityAvailable(): bool
    {
        $quantity_available = $this->getQuantityAvailable();
        $quantity_used = $this->getQuantityUsed();
        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::hasQuantityAvailable code %s quantity_available %s quantity_used %s",
                $this->code,
                $quantity_available,
                $quantity_used
            )
        );

        if ($quantity_available > 0 && $quantity_available <= $quantity_used) return false;
        return true;
    }

    /**
     * @param string $email
     * @param null|string $company
     * @return bool
     */
    public function checkSubject(string $email, ?string $company): bool
    {
        return true;
    }

    /**
     * @param string $email
     * @param null|string $company
     * @throws ValidationException
     */
    public function validate(string $email, ?string $company)
    {
        Log::debug(sprintf("SummitRegistrationPromoCode::validate - email: %s, company: %s", $email, $company ?? ''));

        $this->checkSubject($email, $company);

        if (!$this->hasQuantityAvailable()) {
            throw new ValidationException
            (
                sprintf
                (
                    "Promo code %s has reached max. usage (%s).",
                    $this->getCode(),
                    $this->getQuantityAvailable()
                )
            );
        }
        if (!$this->isLive()) {
            throw new ValidationException(sprintf("The Promo Code %s is not a valid code.", $this->getCode()));
        }
    }

    /**
     * @return bool
     */
    public function isLive(): bool
    {
        // if valid period is not set , that is valid_since_date == valid_until_date == null , then promo code lives forever
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!is_null($this->valid_since_date) && !is_null($this->valid_until_date) && ($now_utc < $this->valid_since_date || $now_utc > $this->valid_until_date)) {
            return false;
        }
        return true;
    }

    public function isInfinite(): bool
    {
        return $this->getQuantityAvailable() == 0;
    }

    /**
     * @param string|null $ownerEmail
     * @param int $usage
     * @throws ValidationException
     */
    public function addUsage(string $ownerEmail, int $usage = 1)
    {
        $quantity_available = $this->getQuantityAvailable();
        $quantity_used = $this->getQuantityUsed();

        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::addUsage code %s usage %s quantity_used %s quantity_available %s ownerEmail %s.",
                $this->code,
                $usage,
                $quantity_used,
                $quantity_available,
                $ownerEmail
            )
        );

        $newVal = $quantity_used + $usage;

        if (!$this->isInfinite() && $newVal > $quantity_available) {
            throw new ValidationException
            (
                sprintf
                (
                    "Promo code %s has reached max. usage (%s).",
                    $this->code,
                    $quantity_available
                )
            );
        }

        $this->quantity_used = $newVal;
        $this->setRedeemed(
            (!$this->isInfinite() && $this->quantity_available == $quantity_used)
        );
    }

    /**
     * @param int $to_restore
     * @param string|null $owner_email
     * @throws ValidationException
     */
    public function removeUsage(int $to_restore, ?string $owner_email = null)
    {
        $quantity_available = $this->getQuantityAvailable();
        $quantity_used = $this->getQuantityUsed();

        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::removeUsage code %s to_restore %s quantity_used %s quantity_available %s owner_email %s",
                $this->code,
                $to_restore,
                $quantity_used,
                $quantity_available,
                $owner_email
            )
        );

        $newVal = $quantity_used - $to_restore;
        if ($newVal < 0) // we want to restore more than we used
            throw new ValidationException
            (
                sprintf
                (

                    "Can not restore %s usages from Promo Code %s (%s).",
                    $to_restore,
                    $this->code,
                    $quantity_used
                )
            );

        $this->quantity_used = $newVal;

        if ($quantity_available > $this->quantity_used) {
            $this->setRedeemed(false);
        }

        Log::info(sprintf("SummitRegistrationPromoCode::removeUsage quantity_used %s", $this->quantity_used));

        $this->setRedeemed( false);
    }

    public function canBeAppliedTo(SummitTicketType $ticketType): bool
    {
        Log::debug(sprintf("SummitRegistrationPromoCode::canBeAppliedTo Ticket type %s.", $ticketType->getId()));
        if ($this->allowed_ticket_types->count() > 0) {
            $criteria = Criteria::create();
            $criteria->where(Criteria::expr()->eq('id', intval($ticketType->getId())));
            return $this->allowed_ticket_types->matching($criteria)->count() > 0;
        }
        return true;
    }

    public function setSourceAdmin()
    {
        $this->source = 'ADMIN';
    }

    /**
     * @return int
     */
    public function getCreatorId()
    {
        try {
            return is_null($this->creator) ? 0 : $this->creator->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasCreator()
    {
        return $this->getCreatorId() > 0;
    }

    const ClassName = 'SUMMIT_PROMO_CODE';

    /**
     * @return string
     */
    public function getClassName()
    {
        return self::ClassName;
    }

    public static $metadata = [
        'class_name' => self::ClassName,
        'code' => 'string',
        'description' => 'string',
        'notes' => 'string',
        'email_sent' => 'boolean',
        'redeemed' => 'boolean',
        'quantity_available' => 'integer',
        'valid_since_date' => 'datetime',
        'valid_until_date' => 'datetime',
        'source' => ['CSV', 'ADMIN'],
        'summit_id' => 'integer',
        'creator_id' => 'integer',
        'allowed_ticket_types' => 'array',
        'allows_to_delegate' => 'boolean',
        'allows_to_reassign' => 'boolean',
    ];

    /**
     * @return array
     */
    public static function getMetadata()
    {
        return self::$metadata;
    }

    /**
     * @return SummitBadgeFeatureType[]
     */
    public function getBadgeFeatures()
    {
        return $this->badge_features;
    }

    /**
     * @return SummitTicketType[]
     */
    public function getAllowedTicketTypes()
    {
        return $this->allowed_ticket_types;
    }

    /**
     * @return int
     */
    public function getQuantityUsed(): int
    {
        return $this->quantity_used;
    }

    /**
     * @return int
     */
    public function getQuantityAvailable(): int
    {
        return $this->quantity_available;
    }

    /**
     * @return int
     */
    public function getQuantityRemaining(): int
    {
        return $this->getQuantityAvailable() - $this->getQuantityUsed();
    }

    /**
     * @param int $quantity_available
     * @throws ValidationException
     */
    public function setQuantityAvailable(int $quantity_available): void
    {
        if ($quantity_available < 0)
            throw new ValidationException("quantity_available should be greater or equal to zero.");
        $this->quantity_available = $quantity_available;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidSinceDate(): ?\DateTime
    {
        return $this->valid_since_date;
    }

    /**
     * @param \DateTime $valid_since_date
     */
    public function setValidSinceDate(?\DateTime $valid_since_date): void
    {
        $this->valid_since_date = $valid_since_date;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidUntilDate(): ?\DateTime
    {
        return $this->valid_until_date;
    }

    /**
     * @param \DateTime $valid_until_date
     */
    public function setValidUntilDate(?\DateTime $valid_until_date): void
    {
        $this->valid_until_date = $valid_until_date;
    }

    /**
     * @return void
     */
    public function isAllowsToDelegate(): bool
    {
        return $this->allows_to_delegate;
    }

    /**
     * @param bool $allows_to_delegate
     * @return void
     */
    public function setAllowsToDelegate(bool $allows_to_delegate): void
    {
        $this->allows_to_delegate = $allows_to_delegate;
    }

    /**
     * @param SummitTicketType $ticket_type
     */
    public function addAllowedTicketType(SummitTicketType $ticket_type)
    {
        if ($this->allowed_ticket_types->contains($ticket_type)) return;
        $this->allowed_ticket_types->add($ticket_type);
    }

    /**
     * @param SummitTicketType $ticket_type
     */
    public function removeAllowedTicketType(SummitTicketType $ticket_type)
    {
        if (!$this->allowed_ticket_types->contains($ticket_type)) return;
        $this->allowed_ticket_types->removeElement($ticket_type);
    }

    public function clearTicketTypes()
    {
        $this->allowed_ticket_types->clear();
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function addBadgeFeatureType(SummitBadgeFeatureType $feature_type)
    {
        if ($this->badge_features->contains($feature_type)) return;
        $this->badge_features->add($feature_type);
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function removeBadgeFeatureType(SummitBadgeFeatureType $feature_type)
    {
        if (!$this->badge_features->contains($feature_type)) return;
        $this->badge_features->removeElement($feature_type);
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @return SummitAttendeeTicket
     * @throws ValidationException
     */
    public function applyTo(SummitAttendeeTicket $ticket): SummitAttendeeTicket
    {
        $badge = $ticket->hasBadge() ? $ticket->getBadge() : null;
        if (is_null($badge))
            throw new ValidationException(sprintf("Ticket %s has not badge set.", $ticket->getId()));
        // apply the promo code code to badge
        $badge->applyPromoCode($this);
        $ticket->setPromoCode($this);
        return $ticket;
    }

    /**
     * @return string
     */
    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
     * @return SummitAttendeeTicket[]
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * @return SummitAttendeeTicket[]
     */
    public function getUnassignedTickets()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('owner'));
        return $this->tickets->matching($criteria);
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function addTicket(SummitAttendeeTicket $ticket): void
    {
        if ($this->tickets->contains($ticket)) return;
        $ticket->setPromoCode($this);
        $this->tickets->add($ticket);
    }

    public function clearTickets()
    {
        $this->tickets->clear();
    }

    public function canBeAppliedToOrder(SummitOrder $order):bool{
        return true;
    }

    /**
     * @return \DateTime|null
     */
    public function getSentDate(): ?\DateTime
    {
        return $this->sent_date;
    }

    /**
     * see https://tipit.avaza.com/project/view/347915#!tab=task-pane&task=3547232
     * @return int
     */
    public function getMaxUsagePerOrder():int{
        // if is infinite , then we can purchase one per time
        if($this->isInfinite()) return 1;
        return $this->getQuantityRemaining();
    }

    /**
     * @return void
     */
    public function isAllowsToReassign(): bool
    {
        return $this->allows_to_reassign;
    }

    /**
     * @param bool $allows_to_reassign
     * @return void
     */
    public function setAllowsToReassign(bool $allows_to_reassign): void
    {
        $this->allows_to_reassign = $allows_to_reassign;
    }
}
