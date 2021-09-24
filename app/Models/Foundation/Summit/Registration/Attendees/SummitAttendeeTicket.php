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

use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundAccepted;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRejected;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestAdmin;
use App\Jobs\Emails\Registration\Refunds\SummitTicketRefundRequestOwner;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\Jobs\ProcessTicketRefundRequest;
use Doctrine\Common\Collections\Criteria;
use App\Models\Foundation\Summit\AllowedCurrencies;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeTicket")
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitAttendeeTicketRepository")
 * Class SummitAttendeeTicket
 * @package models\summit
 */
class SummitAttendeeTicket extends SilverstripeBaseModel
    implements IQREntity
{

    /**
     * @ORM\Column(name="ExternalOrderId", type="string")
     * @var string
     */
    private $external_order_id;

    /**
     * @ORM\Column(name="ExternalAttendeeId", type="string")
     * @var
     */
    private $external_attendee_id;

    /**
     * @ORM\Column(name="TicketBoughtDate", type="datetime")
     * @var \DateTime
     */
    private $bought_date;

    /**
     * @ORM\Column(name="TicketChangedDate", type="datetime")
     * @var \DateTime
     */
    private $changed_date;

    /**
     * @ORM\ManyToOne(targetEntity="SummitTicketType")
     * @ORM\JoinColumn(name="TicketTypeID", referencedColumnName="ID")
     * @var SummitTicketType
     */
    private $ticket_type;

    /**
     * @ORM\ManyToOne(targetEntity="SummitAttendee", inversedBy="tickets")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID", nullable=true)
     * @var SummitAttendee
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitOrder", inversedBy="tickets")
     * @ORM\JoinColumn(name="OrderID", referencedColumnName="ID")
     * @var SummitOrder
     */
    private $order;

    /**
     * @ORM\OneToMany(targetEntity="SummitAttendeeTicketTax", mappedBy="ticket", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitAttendeeTicketTax[]
     */
    private $applied_taxes;

    /**
     * @ORM\OneToMany(targetEntity="SummitAttendeeTicketRefundRequest", mappedBy="ticket", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitAttendeeTicketRefundRequest[]
     */
    private $refund_requests;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitRegistrationPromoCode")
     * @ORM\JoinColumn(name="PromoCodeID", referencedColumnName="ID", nullable=true)
     * @var SummitRegistrationPromoCode
     */
    private $promo_code;

    /**
     * @ORM\Column(name="Hash", type="string")
     * @var string
     */
    private $hash;

    /**
     * @ORM\Column(name="HashCreationDate", type="datetime")
     * @var \DateTime
     */
    private $hash_creation_date;

    /**
     * @ORM\Column(name="Number", type="string")
     * @var string
     */
    private $number;

    /**
     * @ORM\Column(name="Status", type="string")
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="RawCost", type="float")
     * @var float
     */
    private $raw_cost;

    /**
     * @ORM\Column(name="Discount", type="float")
     * @var float
     */
    private $discount;

    /**
     * @ORM\Column(name="Currency", type="string")
     * @var string
     */
    private $currency;

    /**
     * @ORM\Column(name="QRCode", type="string", nullable=true)
     * @var string
     */
    private $qr_code;

    /**
     * @ORM\OneToOne(targetEntity="SummitAttendeeBadge", mappedBy="ticket", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitAttendeeBadge
     */
    private $badge;

    /**
     * @ORM\OneToMany(targetEntity="SummitAttendeeTicketFormerHash", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     * @var SummitAttendeeTicketFormerHash[]
     */
    private $former_hashes;

    /**
     * @ORM\Column(name="IsActive", type="boolean")
     * @var bool
     */
    private $is_active;

    /**
     * SummitAttendeeTicket constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->status = IOrderConstants::ReservedStatus;
        $this->currency = AllowedCurrencies::USD;
        $this->applied_taxes = new ArrayCollection();
        $this->former_hashes = new ArrayCollection();
        $this->refund_requests = new ArrayCollection();
        $this->raw_cost = 0.0;
        $this->discount = 0.0;
        $this->is_active = true;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return \DateTime
     */
    public function getHashCreationDate(): \DateTime
    {
        return $this->hash_creation_date;
    }

    /**
     * @return string
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return float
     */
    public function getRawCost(): ?float
    {
        return $this->raw_cost;
    }

    /**
     * @param float $raw_cost
     */
    public function setRawCost(float $raw_cost): void
    {
        $this->raw_cost = $raw_cost;
    }

    /**
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): ?string
    {
        return "$";
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getQRCode(): ?string
    {
        return $this->qr_code;
    }

    public function generateHash():void
    {
        $token = $this->number;
        if (!is_null($this->order)) {
            $token .= $this->order->getHash();
        }
        $salt                     = random_bytes(16);
        if(!empty($this->hash)){
            $former_hash = new SummitAttendeeTicketFormerHash($this->hash, $this);
            $this->former_hashes->add($former_hash);
        }
        $this->hash               = hash('sha256', $token.$salt.time());
        $this->hash_creation_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @throws ValidationException
     */
    public function sendPublicEditEmail(){
        if (!$this->isPaid())
            throw new ValidationException("ticket is not paid");

        if (!$this->hasOwner())
            throw new ValidationException("ticket must have an assigned owner");

        $this->generateQRCode();
        $this->generateHash();

        SummitAttendeeTicketRegenerateHashEmail::dispatch($this);
    }

    /**
     * @return bool
     */
    public function hasOrder()
    {
        return $this->getOrderId() > 0;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        try {
            return is_null($this->order) ? 0 : $this->order->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return SummitOrder
     */
    public function getOrder(): SummitOrder
    {
        return $this->order;
    }

    /**
     * @param SummitOrder $order
     */
    public function setOrder(SummitOrder $order): void
    {
        $this->order = $order;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function canPubliclyEdit(): bool
    {
        if (empty($this->hash) || is_null($this->hash_creation_date)) return false;
        $ttl_minutes = Config::get("registration.ticket_public_edit_ttl", 30);
        $eol = new \DateTime('now', new \DateTimeZone('UTC'));
        $eol->sub(new \DateInterval('PT' . $ttl_minutes . 'M'));
        if ($this->hash_creation_date <= $eol)
            return false;
        return true;
    }

    /**
     * @return string
     */
    public function generateNumber(): string
    {
        $summit = $this->getOrder()->getSummit();
        $this->number = strtoupper(str_replace(".", "", uniqid($summit->getTicketQRPrefix().'_', true)));
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getChangedDate()
    {
        return $this->changed_date;
    }

    /**
     * @param mixed $changed_date
     */
    public function setChangedDate($changed_date)
    {
        $this->changed_date = $changed_date;
    }

    /**
     * @return string
     */
    public function getExternalOrderId():?string
    {
        return $this->external_order_id;
    }

    /**
     * @param string $external_order_id
     */
    public function setExternalOrderId($external_order_id)
    {
        $this->external_order_id = $external_order_id;
    }

    /**
     * @return string
     */
    public function getExternalAttendeeId():?string
    {
        return $this->external_attendee_id;
    }

    /**
     * @param string $external_attendee_id
     */
    public function setExternalAttendeeId($external_attendee_id)
    {
        $this->external_attendee_id = $external_attendee_id;
    }

    /**
     * @return \DateTime
     */
    public function getBoughtDate()
    {
        return $this->bought_date;
    }

    /**
     * @param \DateTime $bought_date
     */
    public function setBoughtDate($bought_date)
    {
        $this->bought_date = $bought_date;
    }

    /**
     * @return SummitTicketType
     */
    public function getTicketType()
    {
        return $this->ticket_type;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return $this
     */
    public function setTicketType(SummitTicketType $ticket_type)
    {
        $this->ticket_type = $ticket_type;
        $this->raw_cost    = $this->ticket_type->getCost();
        $this->currency    = $this->ticket_type->getCurrency();
        return $this->ticket_type->applyTo($this);
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return SummitAttendeeTicket
     * @throws ValidationException
     */
    public function upgradeTicketType(SummitTicketType $ticket_type){
        if(is_null($this->ticket_type))
            throw new ValidationException("Ticket has not a previous ticket type set.");
        if($this->raw_cost > $ticket_type->getCost())
            throw new ValidationException("Can not assign a ticket type of less value (Downgrade)");
        $this->ticket_type = $ticket_type;
        return $this->ticket_type->applyTo($this);
    }

    /**
     * @return bool
     */
    public function hasTicketType()
    {
        return $this->getTicketTypeId() > 0;
    }

    /**
     * @return int
     */
    public function getTicketTypeId()
    {
        try {
            return is_null($this->ticket_type) ? 0 : $this->ticket_type->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return SummitAttendee|null
     */
    public function getOwner():?SummitAttendee
    {
        return $this->owner;
    }

    /**
     * @param SummitAttendee $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return bool
     */
    public function hasOwner()
    {
        return $this->getOwnerId() > 0;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        try {
            return is_null($this->owner) ? 0 : $this->owner->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function isPaid():bool {
        return $this->status == IOrderConstants::PaidStatus;
    }

    /**
     * @return bool
     */
    public function isCancelled():bool {
        return $this->status == IOrderConstants::CancelledStatus;
    }

    use QRGeneratorTrait;

    /**
     * @return string
     */
    public function generateQRCode(): string
    {
        if(is_null($this->order)){
            throw new ValidationException("ticket has not order set");
        }

        $this->qr_code = $this->generateQRFromFields([
            $this->order->getSummit()->getTicketQRPrefix(),
            $this->number
        ]);

        return $this->qr_code;
    }

    /**
     * @return SummitAttendeeBadge
     */
    public function getBadge(): SummitAttendeeBadge
    {
        return $this->badge;
    }

    /**
     * @param SummitAttendeeBadge $badge
     */
    public function setBadge(SummitAttendeeBadge $badge): void
    {
        $this->badge = $badge;
        $badge->setTicket($this);
    }

    /**
     * @return bool
     */
    public function hasBadge()
    {
        return $this->getBadgeId() > 0 || !is_null($this->badge);
    }

    /**
     * @return int
     */
    public function getBadgeId()
    {
        try {
            return is_null($this->badge) ? 0 : $this->badge->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function setPaid($set_bought_date = true)
    {
        $this->status = IOrderConstants::PaidStatus;
        if($set_bought_date)
            $this->bought_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function setCancelled()
    {
        if ($this->status == IOrderConstants::PaidStatus) return;
        $this->status = IOrderConstants::CancelledStatus;
    }

    /**
     * @return bool
     */
    public function hasPromoCode(): bool
    {
        return $this->getPromoCodeId() > 0;
    }

    /**
     * @return int
     */
    public function getPromoCodeId(): int
    {
        try {
            return is_null($this->promo_code) ? 0 : $this->promo_code->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return SummitRegistrationPromoCode
     */
    public function getPromoCode(): ?SummitRegistrationPromoCode
    {
        return $this->promo_code;
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     */
    public function setPromoCode(SummitRegistrationPromoCode $promo_code): void
    {
        $this->promo_code = $promo_code;
    }

    /**
     * @return bool
     */
    public function isRefundRequested():bool{
        return $this->hasPendingRefundRequests();
    }

    /**
     * @param Member|null $rejectedBy
     * @param string|null $notes
     * @throws ValidationException
     */
    function cancelRefundRequest(?Member $rejectedBy = null, ?string $notes = null):SummitAttendeeTicketRefundRequest {
        if(!$this->hasPendingRefundRequests())
            throw new ValidationException(sprintf("You can not cancel any refund on this ticket"));
        $request = $this->getPendingRefundRequest();

        $request->reject($rejectedBy, $notes);
        SummitTicketRefundRejected::dispatch($this, $request);
        return $request;
    }

    /**
     * @return float
     */
    public function getRefundedAmount():float{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', ISummitRefundRequestConstants::ApprovedStatus));
        $totalRefundedAmount = 0.0;
        foreach($this->refund_requests->matching($criteria) as $request){
            $totalRefundedAmount += $request->getRefundedAmount();
        }
        return $totalRefundedAmount;
    }

    /**
     * @param float $amount
     * @return bool
     * @throws ValidationException
     */
    public function canRefund(float $amount):bool{

        if($this->isFree()){
            throw new ValidationException("Can not refund a Free Ticket.");
        }

        if($this->getFinalAmount() < ($this->getRefundedAmount() + $amount) ){
            throw new ValidationException("Can not refund an amount greater than Amount Paid.");
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isBadgePrinted():bool{
        if($this->hasBadge()){
            $badge = $this->getBadge();
            return $badge->isPrinted();
        }
        return false;
    }

    /**
     * @param Member|null $requestedBy
     * @return SummitAttendeeTicketRefundRequest|null
     * @throws ValidationException
     */
    public function requestRefund(?Member $requestedBy = null): ?SummitAttendeeTicketRefundRequest
    {
        if ($this->status != IOrderConstants::PaidStatus)
            throw new ValidationException(sprintf( "You can not request a refund for this ticket %s ( invalid status %s).", $this->number, $this->status));

        $summit = $this->getOrder()->getSummit();
        $begin_date = $summit->getBeginDate();
        if(is_null($begin_date))
            throw new ValidationException(sprintf( "You can not request a refund for this ticket %s ( summit has not begin date).", $this->number));

        if($this->isBadgePrinted())
            throw new ValidationException(sprintf( "You can not request a refund for this ticket %s ( badge already printed).", $this->number));

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        if($now > $begin_date){
            Log::debug("SummitAttendeeTicket::requestRefund: now is greater than Summit.BeginDate");
            throw new ValidationException("You can not request a refund after summit started.");
        }

        if($this->hasPendingRefundRequests()){
            throw new ValidationException("A refund on a Ticket its already being processed.");
        }

        $interval = $begin_date->diff($now);

        $days_before_event_starts = intval($interval->format('%a'));
        $request = new SummitAttendeeTicketRefundRequest($requestedBy);
        $request->setTicket($this);
        $this->refund_requests->add($request);

        Log::debug(sprintf("SummitAttendeeTicket::requestRefund: days_before_event_starts %s", $days_before_event_starts));

        SummitTicketRefundRequestAdmin::dispatch($this);
        SummitTicketRefundRequestOwner::dispatch($this, $request);

        ProcessTicketRefundRequest::dispatch($this->getId(), $days_before_event_starts);

        return $request;
    }

    /**
     * @return bool
     */
    public function hasPendingRefundRequests():bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', ISummitRefundRequestConstants::RefundRequestedStatus));
        return $this->refund_requests->matching($criteria)->count() > 0;
    }

    /**
     * @return SummitAttendeeTicketRefundRequest|null
     */
    public function getPendingRefundRequest():?SummitAttendeeTicketRefundRequest{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', ISummitRefundRequestConstants::RefundRequestedStatus));
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', ISummitRefundRequestConstants::RefundRequestedStatus));
        $res = $this->refund_requests->matching($criteria)->first();
        return $res == false ? null : $res;
    }

    /**
     * @param Member|null $approvedBy
     * @param float $amount
     * @param string|null $paymentGatewayRes
     * @param string|null $notes
     * @return SummitAttendeeTicketRefundRequest
     * @throws ValidationException
     */
    public function refund
    (
        ?Member $approvedBy,
        float $amount,
        string $paymentGatewayRes = null,
        string $notes = null
    ):SummitAttendeeTicketRefundRequest
    {
        if (!$this->canRefund($amount))
            throw new ValidationException
            (
                sprintf
                (
                    "Can not request a refund on Ticket %s.",
                   $this->id
                )
            );

        $request = $this->getPendingRefundRequest();

        if(is_null($request)){
            $request = new SummitAttendeeTicketRefundRequest($approvedBy);
            $request->setTicket($this);
            $this->refund_requests->add($request);
        }

        $request->approve($approvedBy, $amount, $paymentGatewayRes, $notes);

        SummitTicketRefundAccepted::dispatch($this, $request);

        return $request;
    }

    /**
     * @param array $taxes
     */
    public function applyTaxes(array $taxes)
    {
        $amount = $this->raw_cost;
        $amount -= $this->discount;
        foreach ($taxes as $tax) {
            if (!$tax instanceof SummitTaxType) continue;
            if (!$tax->mustApplyTo($this->ticket_type)) continue;
            $ticketTax = new SummitAttendeeTicketTax();
            $ticketTax->setTicket($this);
            $ticketTax->setTax($tax);
            $ticketTax->setAmount(($amount * $tax->getRate()) / 100.00);
            $this->applied_taxes->add($ticketTax);
        }
    }

    /**
     * @return float
     */
    public function getFinalAmount(): float
    {
        $amount = $this->raw_cost;
        $amount -= $this->discount;
        foreach ($this->applied_taxes as $tax) {
            $amount += $tax->getAmount();
        }
        return $amount;
    }

    /**
     * @return float
     */
    public function getTicketTypeCost():float{
        try {
            return $this->ticket_type->getCost();
        }
        catch (\Exception $ex){
            return 0.0;
        }
    }

    /**
     * @return float
     */
    public function getFinalAmountAdjusted(): float{
        return $this->getFinalAmount() - $this->getRefundedAmount();
    }

    /**
     * @return bool
     */
    public function isFree():bool {
        return $this->getFinalAmount() == 0;
    }

    /**
     * @return float
     */
    public function getTaxesAmount(): float
    {
        $amount = 0.0;
        foreach ($this->getAppliedTaxes() as $appliedTax) {
            $amount += $appliedTax->getAmount();
        }
        return $amount;
    }

    /**
     * @return SummitAttendeeTicketTax[]
     */
    public function getAppliedTaxes()
    {
        return $this->applied_taxes;
    }

    /**
     * @return float
     */
    public function getDiscount(): float
    {
        return $this->discount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setDiscount(float $amount)
    {
        $this->discount = $amount > $this->raw_cost ? $this->raw_cost: $amount;
        return $this;
    }

    public function clearOwner()
    {
        $this->owner = null;
    }

    /**
     * @return string
     */
    public function getOwnerFullName():?string{
        if(is_null($this->owner)) null;
        return $this->owner->getFullName();
    }

    /**
     * @return string
     */
    public function getOwnerFirstName():?string{
        if(is_null($this->owner)) return null;
        return $this->owner->getFirstName();
    }

    /**
     * @return string
     */
    public function getOwnerSurname():?string{
        if(is_null($this->owner)) return null;
        return $this->owner->getSurname();
    }
    /**
     * @return string
     */
    public function getOwnerCompany():?string{
        if(is_null($this->owner)) return null;
        return $this->owner->getCompanyName();
    }

    /**
     * @return string
     */
    public function getOwnerEmail():?string{
        try {
            return is_null($this->owner)? null : $this->owner->getEmail();
        }
        catch (\Exception $ex){
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getBadgeTypeId():?int{
        if(is_null($this->badge)) return 0;
        return $this->badge->getType()->getId();
    }

    /**
     * @return null|string
     */
    public function getBadgeTypeName():?string{
        if(is_null($this->badge)) return null;
        return $this->badge->getType()->getName();
    }

    /**
     * @return null|string
     */
    public function getTicketTypeName():?string{
        if(is_null($this->ticket_type)) return null;
        return $this->ticket_type->getName();
    }

    /**
     * @return array
     */
    public function getBadgeFeaturesNames():array {
        $res = [];
        if(is_null($this->badge)) return [];
        foreach ($this->badge->getFeatures() as $feature){
            $res[] = $feature->getName();
        }
        foreach ($this->badge->getType()->getBadgeFeatures() as $feature){
            if(in_array($feature->getName(),$res)) continue;
            $res[] = $feature->getName();
        }

        return $res;
    }

    public function getBadgeFeatures():array{
        $res = [];
        if(is_null($this->badge)) return [];

        foreach ($this->badge->getFeatures() as $feature){
            $res[$feature->getId()] = $feature;
        }

        foreach ($this->badge->getType()->getBadgeFeatures() as $feature){
            if(key_exists($feature->getId(), $res)) continue;
            $res[$feature->getId()] = $feature;
        }

        return $res;
    }

    /**
     * @return null|string
     */
    public function getPromoCodeValue():?string{
        return $this->hasPromoCode() ? $this->promo_code->getCode() : null;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function canEditTicket(Member $member):bool{
        if($member->isAdmin()) return true;
        // i am ticket owner
        if($this->hasOwner() && $this->owner->getEmail() == $member->getEmail()) return true;
        // i am order owner
        if($this->order->getOwnerEmail() == $member->getEmail()) return true;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): void
    {
        $this->is_active = true;
    }

    public function deActivate(): void
    {
        $this->is_active = false;
    }

    public function getRefundedRequests(){
        return $this->refund_requests;
    }
}
