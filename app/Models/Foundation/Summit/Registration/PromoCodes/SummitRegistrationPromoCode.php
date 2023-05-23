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
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitRegistrationPromoCodeRepository")
 * @ORM\Table(name="SummitRegistrationPromoCode")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({"SummitRegistrationPromoCode" = "SummitRegistrationPromoCode",
 *     "SpeakerSummitRegistrationPromoCode" = "SpeakerSummitRegistrationPromoCode",
 *     "MemberSummitRegistrationPromoCode" = "MemberSummitRegistrationPromoCode",
 *     "SponsorSummitRegistrationPromoCode" = "SponsorSummitRegistrationPromoCode",
 *     "SummitRegistrationDiscountCode" = "SummitRegistrationDiscountCode",
 *     "MemberSummitRegistrationDiscountCode" = "MemberSummitRegistrationDiscountCode",
 *     "SpeakerSummitRegistrationDiscountCode" = "SpeakerSummitRegistrationDiscountCode",
 *     "SponsorSummitRegistrationDiscountCode" = "SponsorSummitRegistrationDiscountCode",
 *     "SpeakersRegistrationDiscountCode" = "SpeakersRegistrationDiscountCode",
 *     "SpeakersSummitRegistrationPromoCode" = "SpeakersSummitRegistrationPromoCode"
 * })
 * Class SummitRegistrationPromoCode
 * @package models\summit
 */
class SummitRegistrationPromoCode extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Code", type="string")
     * @var string
     */
    protected $code;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="ExternalId", type="string")
     * @var string
     */
    protected $external_id;

    /**
     * @ORM\Column(name="EmailSent", type="boolean")
     * @var boolean
     */
    protected $email_sent;

    /**
     * @ORM\Column(name="Redeemed", type="boolean")
     * @var boolean
     */
    protected $redeemed;

    /**
     * @ORM\Column(name="Source", type="string")
     * @var string
     */
    protected $source;

    /**
     * @ORM\Column(name="QuantityAvailable", type="integer")
     * @var int
     */
    protected $quantity_available;

    /**
     * @ORM\Column(name="QuantityUsed", type="integer")
     * @var int
     */
    protected $quantity_used;

    /**
     * @ORM\Column(name="ValidSinceDate", type="datetime")
     * @var \DateTime
     */
    protected $valid_since_date;

    /**
     * @ORM\Column(name="ValidUntilDate", type="datetime")
     * @var \DateTime
     */
    protected $valid_until_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Summit", inversedBy="promo_codes")
     * @ORM\JoinColumn(name="SummitID", referencedColumnName="ID")
     * @var Summit
     */
    protected $summit;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="CreatorID", referencedColumnName="ID")
     * @var Member
     */
    protected $creator;

    /**
     * @ORM\ManyToMany(targetEntity="SummitBadgeFeatureType")
     * @ORM\JoinTable(name="SummitRegistrationPromoCode_BadgeFeatures",
     *      joinColumns={@ORM\JoinColumn(name="SummitRegistrationPromoCodeID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitBadgeFeatureTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitBadgeFeatureType[]
     */
    protected $badge_features;

    /**
     * @ORM\ManyToMany(targetEntity="SummitTicketType")
     * @ORM\JoinTable(name="SummitRegistrationPromoCode_AllowedTicketTypes",
     *      joinColumns={@ORM\JoinColumn(name="SummitRegistrationPromoCodeID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitTicketTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitTicketType[]
     */
    protected $allowed_ticket_types;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Tag", cascade={"persist"}, inversedBy="events", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SummitRegistrationPromoCode_Tags",
     *      joinColumns={@ORM\JoinColumn(name="SummitRegistrationPromoCodeID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TagID", referencedColumnName="ID")}
     *      )
     * @var Tag[]
     */
    private $tags;

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
     * @return bool
     */
    public function isEmailSent()
    {
        return $this->email_sent;
    }

    /**
     * @param bool $email_sent
     */
    public function setEmailSent($email_sent)
    {
        $this->email_sent = $email_sent;
    }

    /**
     * @return bool
     */
    public function isRedeemed()
    {
        return $this->redeemed;
    }

    /**
     * @param bool $redeemed
     */
    public function setRedeemed($redeemed)
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
        $this->badge_features = new ArrayCollection();
        $this->allowed_ticket_types = new ArrayCollection();
        $this->tags = new ArrayCollection();
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
        $quantity_available = $this->quantity_available;
        $quantity_used = $this->quantity_used;

        if ($quantity_available > 0 && $quantity_available <= $quantity_used) return false;
        return true;
    }

    /**
     * @param string $email
     * @param null|string $company
     * @return bool
     * @throw ValidationException
     */
    public function checkSubject(string $email, ?string $company): bool
    {
        return true;
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

    /**
     * @param string|null $ownerEmail
     * @param int $usage
     * @throws ValidationException
     */
    public function addUsage(string $ownerEmail, int $usage = 1)
    {

        $quantity_used = $this->quantity_used;
        $quantity_available = $this->quantity_available;

        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::addUsage code %s usage %s quantity_used %s quantity_available %s.",
                $this->code,
                $usage,
                $quantity_used,
                $quantity_available
            )
        );

        $newVal = $quantity_used + $usage;

        if ($quantity_available > 0 && $newVal > $quantity_available) {
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
    }

    /**
     * @param int $to_restore
     * @param string|null $owner_email
     * @throws ValidationException
     */
    public function removeUsage(int $to_restore, string $owner_email = null)
    {

        $quantity_used = $this->quantity_used;
        $quantity_available = $this->quantity_available;

        Log::debug
        (
            sprintf
            (
                "SummitRegistrationPromoCode::removeUsage code %s to_restore %s quantity_used %s quantity_available %s",
                $this->code,
                $to_restore,
                $quantity_used,
                $quantity_available
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

        Log::info(sprintf("SummitRegistrationPromoCode::removeUsage quantity_used %s", $this->quantity_used));
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
        'email_sent' => 'boolean',
        'redeemed' => 'boolean',
        'quantity_available' => 'integer',
        'valid_since_date' => 'datetime',
        'valid_until_date' => 'datetime',
        'source' => ['CSV', 'ADMIN'],
        'summit_id' => 'integer',
        'creator_id' => 'integer',
        'allowed_ticket_types' => 'array',
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
     * @param int $quantity_available
     * @throws ValidationException
     */
    public function setQuantityAvailable(int $quantity_available): void
    {
        if ($quantity_available < 0)
            throw new ValidationException("quantity_available should be greater than zero.");
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
}
