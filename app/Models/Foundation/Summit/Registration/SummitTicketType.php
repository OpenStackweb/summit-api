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
use App\Models\Foundation\Summit\AllowedCurrencies;
use App\Models\Foundation\Summit\ScheduleEntity;
use App\Models\Utils\Traits\FinancialTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitTicketType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitTicketTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'ticket_types')])]
#[ORM\HasLifecycleCallbacks] // Class SummitTicketType
class SummitTicketType extends SilverstripeBaseModel implements ISummitTicketType
{
    use SummitOwned;

    use FinancialTrait;

    use ScheduleEntity;

    const USD_Currency = 'USD';
    const EUR_Currency = 'EUR';
    const GBP_Currency = 'GBP';
    const CAD_Currency = 'CAD';
    const WON_Currency = 'KRW';

    const AmountFree = 0.0;

    const QtyInfinite = 0;

    const AllowedCurrencies = [
        self::USD_Currency,
        self::EUR_Currency,
        self::GBP_Currency,
        self::CAD_Currency,
        self::WON_Currency,
    ];

    const Audience_All = 'All';
    const Audience_With_Invitation = 'WithInvitation';
    const Audience_Without_Invitation = 'WithoutInvitation';

    const AllowedAudience = [
        self::Audience_All,
        self::Audience_With_Invitation,
        self::Audience_Without_Invitation,
    ];

    const Subtype_Regular = 'Regular';
    const Subtype_PrePaid = 'PrePaid';

    const SubTypes = [
        self::Subtype_Regular,
        self::Subtype_PrePaid,
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalId', type: 'string')]
    private $external_id;

    /**
     * @var double
     */
    #[ORM\Column(name: 'Cost', type: 'float')]
    private $cost;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Currency', type: 'string')]
    private $currency;

    /**
     * @var int
     */
    #[ORM\Column(name: 'QuantityToSell', type: 'integer')]
    private $quantity_2_sell;

    /**
     * @var int
     */
    #[ORM\Column(name: 'QuantitySold', type: 'integer')]
    private $quantity_sold;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MaxQuantityToSellPerOrder', type: 'integer')]
    private $max_quantity_per_order;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SaleStartDate', type: 'datetime')]
    private $sales_start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SaleEndDate', type: 'datetime')]
    private $sales_end_date;

    /**
     * @var SummitTaxType[]
     */
    #[ORM\ManyToMany(targetEntity: \SummitTaxType::class, mappedBy: 'ticket_types')]
    private $applied_taxes;

    /**
     * @var SummitBadgeType
     */
    #[ORM\JoinColumn(name: 'BadgeTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitBadgeType::class)]
    private $badge_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Audience', type: 'string')]
    private $audience;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AllowsToDelegate', type: 'boolean')]
    private $allows_to_delegate;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AllowsReassignRelatedTickets', type: 'boolean')]
    private $allows_reassign_related_tickets;

    /**
     * @var SummitOrderExtraQuestionType[]
     */
    #[ORM\ManyToMany(targetEntity: \models\summit\SummitOrderExtraQuestionType::class, mappedBy: 'allowed_ticket_types')]
    private $extra_question_types;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getExternalId():?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;
    }

    public function __construct()
    {
        parent::__construct();
        $this->cost = 0.0;
        $this->max_quantity_per_order = 0;
        $this->quantity_2_sell = 0;
        $this->quantity_sold = 0;
        $this->currency = AllowedCurrencies::USD;
        $this->applied_taxes = new ArrayCollection();
        $this->sales_start_date = null;
        $this->sales_end_date = null;
        $this->audience = self::Audience_All;
        $this->allows_to_delegate = false;
        $this->allows_reassign_related_tickets = true;
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws ValidationException
     */
    #[ORM\PreRemove] // :
    public function preRemoveHandler(LifecycleEventArgs $args){
       if(Config::get('registration.validate_ticket_type_removal', true) && $this->quantity_sold > 0)
            throw new ValidationException
            (
                sprintf
                (
                    "Can not delete ticket type %s because has sold tickets.", $this->getId()
                )
            );
    }

    /**
     * @return ArrayCollection|SummitTaxType[]
     */
    public function getAppliedTaxes()
    {
        return $this->applied_taxes;
    }

    /**
     * @param SummitTaxType $tax
     */
    public function addAppliedTax(SummitTaxType $tax){
        if($this->applied_taxes->contains($tax)) return;
        $this->applied_taxes->add($tax);
    }

    /**
     * @param SummitTaxType $tax
     */
    public function removeAppliedTax(SummitTaxType $tax){
        if(!$this->applied_taxes->contains($tax)) return;
        $this->applied_taxes->removeElement($tax);
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getCostInCents(): int
    {
        return self::convertToCents($this->cost);
    }

    /**
     * @param float $cost
     */
    public function setCost(float $cost): void
    {
        $this->cost = $cost;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @throws ValidationException
     */
    public function setCurrency(string $currency): void
    {
        if(!in_array($currency, self::AllowedCurrencies))
            throw new ValidationException
            (
                sprintf
                (
                    "Currency %s is not allowed.",
                    $currency
                )
            );

        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getQuantity2Sell(): int
    {
        return $this->quantity_2_sell;
    }

    /**
     * @param int $quantity_2_sell
     * @throws ValidationException
     */
    public function setQuantity2Sell(int $quantity_2_sell): void
    {
        if($quantity_2_sell < 0){
            throw new ValidationException("quantity_2_sell should be greater than zero.");
        }
        $this->quantity_2_sell = $quantity_2_sell;
    }

    /**
     * @return int
     */
    public function getMaxQuantityPerOrder(): int
    {
        return $this->max_quantity_per_order;
    }

    /**
     * @param int $max_quantity_per_order
     * @throws ValidationException
     */
    public function setMaxQuantityPerOrder(int $max_quantity_per_order): void
    {
        if($max_quantity_per_order < 0){
            throw new ValidationException("max_quantity_per_order should be greater than zero.");
        }
        $this->max_quantity_per_order = $max_quantity_per_order;
    }

    /**
     * @return \DateTime|null
     */
    public function getSalesStartDate(): ?\DateTime
    {
        return $this->sales_start_date;
    }

    /**
     * @param \DateTime|null $sales_start_date
     */
    public function setSalesStartDate(?\DateTime $sales_start_date): void
    {
        $this->sales_start_date = $sales_start_date;
    }

    public function clearSalesStartDate():void{
        $this->sales_start_date  = null;
    }

    /**
     * @return \DateTime|null
     */
    public function getSalesEndDate(): ?\DateTime
    {
        return $this->sales_end_date;
    }

    /**
     * @param \DateTime|null $sales_end_date
     */
    public function setSalesEndDate(?\DateTime $sales_end_date): void
    {
        $this->sales_end_date = $sales_end_date;
    }

    public function clearSalesEndDate():void{
        $this->sales_end_date = null;
    }

    /**
     * @return \DateTime
     */
    public function getLocalSalesStartDate(): \DateTime
    {
        return $this->getSummit()->convertDateFromUTC2TimeZone($this->sales_start_date);
    }

    /**
     * @return \DateTime
     */
    public function getLocalSalesEndDate(): \DateTime
    {
        return $this->getSummit()->convertDateFromUTC2TimeZone($this->sales_end_date);
    }

    /**
     * @return int
     */
    public function getQuantitySold(): int
    {
        return $this->quantity_sold;
    }

    /**
     * @return bool
     */
    public function canSell():bool {
        if($this->isSoldOut()) {
            Log::warning(sprintf("SummitTicketType::canSell ticket %s is sold out", $this->id));
            return false;
        }
        return $this->isLive();
    }

    /**
     * @param string $currency
     * @return string
     */
    public static function getSymbolForCurrency(string $currency):string{
        switch ($currency){
            case self::USD_Currency:
                return '$';
            case self::EUR_Currency:
                return '€';
            case self::GBP_Currency:
                return '£';
            case self::WON_Currency:
                return '₩';
            case self::CAD_Currency:
                return 'C$';
            default:
                return '$';
        }
    }
    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return self::getSymbolForCurrency($this->currency);
    }

    /**
     * @return float
     */
    public function getFinalAmount(): float
    {
        $amount = $this->cost;
        foreach ($this->applied_taxes as $tax) {
            $amount += $tax->applyTo($this->cost, false);
        }
        return $amount;
    }
    /**
     * @return bool
     */
    public function isSoldOut():bool{
        $quantity_2_sell = $this->quantity_2_sell;
        $quantity_sold = $this->quantity_sold;
        // no limit
        if($quantity_2_sell === 0) return false;

        return $quantity_2_sell <= $quantity_sold;
    }

    /**
     * @return bool
     */
    public function isLive(){
        // if valid period is not set , that is valid_since_date == valid_until_date == null , then ticket code lives forever
        $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
        if(!is_null($this->sales_start_date) && $now_utc < $this->sales_start_date){
            Log::warning(sprintf("SummitTicketType::isLive ticket %s is not live (now_utc < sales_start_date)", $this->id));
            return false;
        }
        if(!is_null($this->sales_end_date) && $now_utc > $this->sales_end_date){
            Log::warning(sprintf("SummitTicketType::isLive ticket %s is not live (now_utc < sales_end_date)", $this->id));
            return false;
        }
        return true;
    }

    /**
     * @param int $qty
     * @return int
     * @throws ValidationException
     */
    public function sell(int $qty = 1):int {

        $quantity_sold = $this->quantity_sold;
        $quantity_2_sell = $this->quantity_2_sell;
        $max_quantity_per_order = $this->max_quantity_per_order;

        Log::debug
        (
            sprintf
            (
                "SummitTicketType::sell id %s qty %s quantity_sold %s quantity_2_sell %s max_quantity_per_order %s",
                $this->id,
                $qty,
                $quantity_sold,
                $quantity_2_sell,
                $max_quantity_per_order
            )
        );

        $unlimited = $quantity_2_sell === 0;
        $unlimited_per_order = $max_quantity_per_order === 0;

        if(!$unlimited_per_order){
            if($qty > $max_quantity_per_order){
                throw new ValidationException
                (
                    sprintf
                    (
                        "Can not sell more tickets than max. available per order (%s).",
                        $max_quantity_per_order
                    )
                );
            }
        }
        $newVal = $quantity_sold + $qty;
        if(!$unlimited) {
            if ($qty > $quantity_2_sell) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Can not sell more tickets than max. total available (%s).",
                        $quantity_2_sell
                    )
                );
            }

            if ($newVal > $quantity_2_sell) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Can not sell more ticket than available ones (%s).",
                        ($quantity_2_sell - $quantity_sold)
                    )
                );
            }
        }

        $this->quantity_sold = $newVal;

        return $this->quantity_sold;
    }

    /**
     * @param int $qty
     * @return int
     * @throws ValidationException
     */
    public function restore(int $qty):int{

        $quantity_sold = $this->quantity_sold;

        Log::debug
        (
            sprintf
            (
                "SummitTicketType::restore qty %s ticket type %s quantity_sold %s",
                $qty,
                $this->id,
                $quantity_sold
            )
        );

        $newVal = $quantity_sold - $qty;

        if($newVal < 0)
            throw new ValidationException
            (
                sprintf
                (
                    "Can not restore a greater quantity (%s) than sold one (%s).",
                    $qty,
                    $quantity_sold
                )
            );

        $this->quantity_sold  = $newVal;

        Log::info(sprintf("SummitTicketType::restore qty_2_restore %s final qty %s", $qty, $this->quantity_sold));

        return $this->quantity_sold;
    }

    /**
     * @return int
     */
    public function getBadgeTypeId(){
        $res = $this->getBadgeType();
        try {
            return is_null($res) ? 0: $res->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return SummitBadgeType
     */
    public function getBadgeType(): ?SummitBadgeType
    {
        $res = $this->badge_type;
        if(is_null($res)){
            $res = $this->summit->getDefaultBadgeType();
        }
        return $res;
    }

    /**
     * @return bool
     */
    public function hasBadgeType():bool{
        return $this->getBadgeTypeId() > 0;
    }
    /**
     * @param SummitBadgeType $badge_type
     */
    public function setBadgeType(SummitBadgeType $badge_type): void
    {
        $this->badge_type = $badge_type;
    }

    /**
     * @return string
     */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * @param string $audience
     */
    public function setAudience(string $audience)
    {
        if(!in_array($audience, self::AllowedAudience))
            throw new ValidationException(sprintf("audience %s is not allowed", $audience));

        $this->audience = $audience;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @return SummitAttendeeTicket
     */
    public function applyTo(SummitAttendeeTicket $ticket){
        if($this->hasBadgeType()){
            $badge = $ticket->hasBadge() ? $ticket->getBadge() : new SummitAttendeeBadge();
            $ticket->setBadge($badge->applyTicketType($this));
        }
        return $ticket;
    }

    public function isFree():bool{
        return $this->getCost() === 0.0;
    }

    public function getSubType(): string {
        return self::Subtype_Regular;
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
     * @return void
     */
    public function isAllowsToReassignRelatedTickets(): bool
    {
        return $this->allows_reassign_related_tickets;
    }

    /**
     * @param bool $allows_to_delegate
     * @return void
     */
    public function setAllowsToReassignRelatedTickets(bool $allows_reassign_related_tickets): void
    {
        $this->allows_reassign_related_tickets = $allows_reassign_related_tickets;
    }
}