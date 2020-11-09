<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Events\PaymentSummitRegistrationOrderConfirmed;
use App\Events\RequestedSummitOrderRefund;
use App\Events\SummitOrderCanceled;
use App\Events\SummitOrderRefundAccepted;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitOrderRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="orders"
 *     )
 * })
 * @ORM\Table(name="SummitOrder")
 * Class SummitOrder
 * @package models\summit
 */
class SummitOrder extends SilverstripeBaseModel implements IQREntity
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Number", type="string")
     * @var string
     */
    private $number;

    /**
     * @ORM\Column(name="ExternalId", type="string")
     * @var string
     */
    private $external_id;

    /**
     * @ORM\Column(name="Status", type="string")
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="PaymentMethod", type="string")
     * @var string
     */
    private $payment_method;

    /**
     * @ORM\Column(name="QRCode", type="string", nullable=true)
     * @var string
     */
    private $qr_code;

    /**
     * @ORM\Column(name="OwnerFirstName", type="string")
     * @var string
     */
    private $owner_first_name;

    /**
     * @ORM\Column(name="OwnerSurname", type="string")
     * @var string
     */
    private $owner_surname;

    /**
     * @ORM\Column(name="OwnerEmail", type="string")
     * @var string
     */
    private $owner_email;

    /**
     * @ORM\Column(name="OwnerCompany", type="string")
     * @var string
     */
    private $owner_company;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Company")
     * @ORM\JoinColumn(name="CompanyID", referencedColumnName="ID", nullable=true)
     * @var Company
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="summit_registration_orders")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID", nullable=true)
     * @var Member
     */
    private $owner;

    /**
     * @ORM\Column(name="BillingAddress1", type="string")
     * @var string
     */
    private $billing_address_1;

    /**
     * @ORM\Column(name="BillingAddress2", type="string")
     * @var string
     */
    private $billing_address_2;

    /**
     * @ORM\Column(name="BillingAddressZipCode", type="string")
     * @var string
     */
    private $billing_address_zip_code;

    /**
     * @ORM\Column(name="BillingAddressCity", type="string")
     * @var string
     */
    private $billing_address_city;

    /**
     * @ORM\Column(name="BillingAddressState", type="string")
     * @var string
     */
    private $billing_address_state;

    /**
     * @ORM\Column(name="BillingAddressCountryISOCode", type="string")
     * @var string
     */
    private $billing_address_country_iso_code;

    /**
     * @ORM\Column(name="ApprovedPaymentDate", type="datetime")
     * @var \DateTime
     */
    private $approved_payment_date;

    /**
     * @ORM\Column(name="LastError", type="string")
     * @var string
     */
    private $last_error;

    /**
     * @ORM\Column(name="PaymentGatewayClientToken", type="string")
     * @var string
     */
    private $payment_gateway_client_token;

    /**
     * @ORM\Column(name="PaymentGatewayCartId", type="string")
     * @var string
     */
    private $payment_gateway_cart_id;

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
     * @ORM\OneToMany(targetEntity="SummitAttendeeTicket", mappedBy="order", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitAttendeeTicket[]
     */
    private $tickets;

    /**
     * @ORM\OneToMany(targetEntity="SummitOrderExtraQuestionAnswer", mappedBy="order", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitOrderExtraQuestionAnswer[]
     */
    private $extra_question_answers;

    /**
     * @ORM\Column(name="RefundedAmount", type="float")
     * @var float
     */
    private $refunded_amount;

    /**
     * @var \DateTime
     */
    private $disclaimer_accepted_date;

    /**
     * @ORM\Column(name="LastReminderEmailSentDate", type="datetime")
     * @var \DateTime
     */
    private $last_reminder_email_sent_date;

    /**
     * SummitOrder constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->tickets                = new ArrayCollection();
        $this->extra_question_answers = new ArrayCollection();
        $this->status                 = IOrderConstants::ReservedStatus;
        $this->payment_method         = IOrderConstants::OnlinePaymentMethod;
        $this->refunded_amount        = 0.0;
    }


    public function setPaymentMethodOffline(){
        $this->payment_method = IOrderConstants::OfflinePaymentMethod;
    }

    public function generateHash(){
        $email = $this->getOwnerEmail();
        if(empty($email))
            throw new ValidationException("owner email is null");

        $fname = $this->getOwnerFirstName();
        if(empty($fname))
            throw new ValidationException("owner first name is null");

        $lname = $this->getOwnerSurname();
        if(empty($lname))
            throw new ValidationException("owner first last name is null");

        $token = $this->number.'.'.$email.'.'.$fname.".".$lname;
        $token = $token . random_bytes(16).time();
        $this->hash = hash('sha256', $token);
        $this->hash_creation_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function canPubliclyEdit():bool {
        if(empty($this->hash) || is_null($this->hash_creation_date)) return false;
        $ttl_minutes = Config::get("registration.order_public_edit_ttl", 10);
        $eol = new \DateTime('now', new \DateTimeZone('UTC'));
        $eol->sub(new \DateInterval('PT'.$ttl_minutes.'M'));
        if($this->hash_creation_date <= $eol)
            return false;
        return true;
    }

    /**
     * @return string
     */
    public function generateNumber():string{
        $this->number = strtoupper(str_replace(".","", uniqid($this->summit->getOrderQRPrefix().'_', true)));
        $this->generateQRCode();
        return $this->number;
    }

    /**
     * @return string
     */
    public function getNumber(): string
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


    public function setPaidStatus(){
        $this->status = IOrderConstants::PaidStatus;
        $this->approved_payment_date = new \DateTime('now', new \DateTimeZone('UTC'));

    }

    public function setPaid(){
        Log::debug(sprintf("SummitOrder::setPaid order %s", $this->id));
        if($this->isPaid()){
            Log::warning(sprintf("SummitOrder %s is already Paid.", $this->getId()));
            return;
        }

        $this->setPaidStatus();

        foreach($this->tickets as $ticket){
            $ticket->setPaid();
        }

        Event::fire(new PaymentSummitRegistrationOrderConfirmed($this->getId()));
    }

    /**
     * @param null|string $error
     */
    public function setPaymentError(?string $error):void{
        if(empty($error)) return;
        $this->status = IOrderConstants::ErrorStatus;
        $this->last_error = $error;
    }

    public function setConfirmed(){
        if($this->status == IOrderConstants::ReservedStatus)
            $this->status = IOrderConstants::ConfirmedStatus;
    }

    /**
     * @param bool $sendMail
     */
    public function setCancelled(bool $sendMail = true):void {
        $ignore_statuses = [ IOrderConstants::PaidStatus,  IOrderConstants::CancelledStatus];

        if(in_array($this->status, $ignore_statuses)) return;
        $this->status = IOrderConstants::CancelledStatus;
        list($tickets_to_return, $promo_codes_to_return) = $this->calculateTicketsAndPromoCodesToReturn();

        foreach ($this->getTickets() as $ticket){
            $ticket->setCancelled();
        }

        Event::fire(new SummitOrderCanceled($this->id, $sendMail, $tickets_to_return, $promo_codes_to_return));
    }

    /**
     * @return array
     */
    public function calculateTicketsAndPromoCodesToReturn():array {
        $tickets_to_return = [];
        $promo_codes_to_return = [];

        foreach($this->tickets as $ticket){
            if($ticket->isCancelled()) continue;
            if($ticket->isRefunded()) continue;
            if(!isset($tickets_to_return[$ticket->getTicketTypeId()]))
                $tickets_to_return[$ticket->getTicketTypeId()] = 0;
            $tickets_to_return[$ticket->getTicketTypeId()] += 1;
            if($ticket->hasPromoCode()){
                if(!isset($promo_codes_to_return[$ticket->getPromoCode()->getCode()]))
                    $promo_codes_to_return[$ticket->getPromoCode()->getCode()] = 0;
                $promo_codes_to_return[$ticket->getPromoCode()->getCode()] +=1;
            }
        }
        return [$tickets_to_return, $promo_codes_to_return];
    }

    /**
     * @throws ValidationException
     */
    public function requestRefund():void{
        $summit = $this->getSummit();

        $begin_date = $summit->getBeginDate();
        if(is_null($begin_date)) return;

        // check tickets badge printings

        if($this->getRawAmount() == 0 )

        foreach ($this->tickets as $ticket){
            if($ticket->isBadgePrinted()){
                throw new ValidationException(sprintf( "You can not request a refund for this ticket %s (badge already printed).", $ticket->getNumber()));
            }
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        if($now > $begin_date){
            Log::debug("SummitOrder::requestRefund: now is greater than Summit.BeginDate");
            throw new ValidationException("You can not request a refund after summit started.");
        }

        $interval = $begin_date->diff($now);

        $days_before_event_starts = intval($interval->format('%a'));

        Log::debug(sprintf("SummitOrder::requestRefund: days_before_event_starts %s", $days_before_event_starts));

        if($this->status != IOrderConstants::PaidStatus){
            throw new ValidationException("You can not request a refund on this order.");
        }

        $this->status = IOrderConstants::RefundRequestedStatus;

        foreach ($this->tickets as $ticket){
            $ticket->setRefundRequests();
        }

        Event::fire(new RequestedSummitOrderRefund($this->getId(), $days_before_event_starts));
    }

    function cancelRefundRequest():void {
        if(!$this->isRefundRequested())
            throw new ValidationException(sprintf("You can not cancel any refund on this order"));

        $this->status = IOrderConstants::PaidStatus;

        foreach ($this->tickets as $ticket){
            $ticket->setPaid(false);
        }
    }
    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

    /**
     * @param string $payment_method
     */
    public function setPaymentMethod(string $payment_method): void
    {
        $this->payment_method = $payment_method;
    }

    /**
     * @return string
     */
    public function getQRCode(): ?string
    {
        return $this->qr_code;
    }

    /**
     * @return string
     */
    public function getOwnerFirstName(): ?string
    {
        if($this->hasOwner()){
            return $this->owner->getFirstName();
        }
        return $this->owner_first_name;
    }

    /**
     * @param string $owner_first_name
     */
    public function setOwnerFirstName(string $owner_first_name): void
    {
        $this->owner_first_name = $owner_first_name;
    }

    /**
     * @return string
     */
    public function getOwnerSurname(): ?string
    {
        if($this->hasOwner()){
            return $this->owner->getLastName();
        }
        return $this->owner_surname;
    }

    /**
     * @param string $owner_surname
     */
    public function setOwnerSurname(string $owner_surname): void
    {
        $this->owner_surname = $owner_surname;
    }

    /**
     * @return string
     */
    public function getOwnerEmail(): ?string
    {
        if(!is_null($this->owner)){
            return $this->owner->getEmail();
        }
        return $this->owner_email;
    }

    /**
     * @param string $owner_email
     */
    public function setOwnerEmail(string $owner_email): void
    {
        $this->owner_email = strtolower($owner_email);
    }

    /**
     * @return string
     */
    public function getOwnerCompany(): ?string
    {
        if($this->hasCompany())
            return $this->company->getName();
        return $this->owner_company;
    }

    /**
     * @param string $owner_company
     */
    public function setOwnerCompany(string $owner_company): void
    {
        $this->owner_company = $owner_company;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return Member
     */
    public function getOwner(): ?Member
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner(Member $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getBillingAddress1(): ?string
    {
        return $this->billing_address_1;
    }

    /**
     * @param string $billing_address_1
     */
    public function setBillingAddress1(string $billing_address_1): void
    {
        $this->billing_address_1 = $billing_address_1;
    }

    /**
     * @return string
     */
    public function getBillingAddress2(): ?string
    {
        return $this->billing_address_2;
    }

    /**
     * @param string $billing_address_2
     */
    public function setBillingAddress2(string $billing_address_2): void
    {
        $this->billing_address_2 = $billing_address_2;
    }

    /**
     * @return string
     */
    public function getBillingAddressZipCode(): ?string
    {
        return $this->billing_address_zip_code;
    }

    /**
     * @param string $billing_address_zip_code
     */
    public function setBillingAddressZipCode(string $billing_address_zip_code): void
    {
        $this->billing_address_zip_code = $billing_address_zip_code;
    }

    /**
     * @return string
     */
    public function getBillingAddressCity(): ?string
    {
        return $this->billing_address_city;
    }

    /**
     * @param string $billing_address_city
     */
    public function setBillingAddressCity(string $billing_address_city): void
    {
        $this->billing_address_city = $billing_address_city;
    }

    /**
     * @return string
     */
    public function getBillingAddressState(): ?string
    {
        return $this->billing_address_state;
    }

    /**
     * @param string $billing_address_state
     */
    public function setBillingAddressState(string $billing_address_state): void
    {
        $this->billing_address_state = $billing_address_state;
    }

    /**
     * @return string
     */
    public function getBillingAddressCountryIsoCode(): ?string
    {
        return $this->billing_address_country_iso_code;
    }

    /**
     * @param string $billing_address_country_iso_code
     */
    public function setBillingAddressCountryIsoCode(string $billing_address_country_iso_code): void
    {
        $this->billing_address_country_iso_code = $billing_address_country_iso_code;
    }

    /**
     * @return \DateTime
     */
    public function getApprovedPaymentDate(): \DateTime
    {
        return $this->approved_payment_date;
    }

    /**
     * @param \DateTime $approved_payment_date
     */
    public function setApprovedPaymentDate(\DateTime $approved_payment_date): void
    {
        $this->approved_payment_date = $approved_payment_date;
    }

    /**
     * @return string
     */
    public function getLastError(): ?string
    {
        return $this->last_error;
    }

    /**
     * @param string $last_error
     */
    public function setLastError(string $last_error): void
    {
        $this->last_error = $last_error;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayClientToken(): ?string
    {
        return $this->payment_gateway_client_token;
    }

    /**
     * @param string $payment_gateway_client_token
     */
    public function setPaymentGatewayClientToken(string $payment_gateway_client_token): void
    {
        $this->payment_gateway_client_token = $payment_gateway_client_token;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayCartId(): ?string
    {
        return $this->payment_gateway_cart_id;
    }

    /**
     * @param string $payment_gateway_cart_id
     */
    public function setPaymentGatewayCartId(string $payment_gateway_cart_id): void
    {
        $this->payment_gateway_cart_id = $payment_gateway_cart_id;
    }

    /**
     * @return string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @return \DateTime
     */
    public function getHashCreationDate(): ?\DateTime
    {
        return $this->hash_creation_date;
    }

    /**
     * @return ArrayCollection|SummitAttendeeTicket[]
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function addTicket(SummitAttendeeTicket $ticket){
        if($this->tickets->contains($ticket)) return;
        $this->tickets->add($ticket);
        $ticket->setOrder($this);
    }

    /**
     * @param int $ticket_id
     * @return SummitAttendeeTicket|null
     */
    public function getTicketById(int $ticket_id):?SummitAttendeeTicket{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($ticket_id)));
        $ticket = $this->tickets->matching($criteria)->first();
        return $ticket === false ? null : $ticket;
    }

    /**
     * @return SummitOrderExtraQuestionAnswer[]
     */
    public function getExtraQuestionAnswers()
    {
        return $this->extra_question_answers;
    }

    public function clearExtraQuestionAnswers(){
        $this->extra_question_answers->clear();
    }

    public function addExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer){
        if($this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->add($answer);
        $answer->setOrder($this);
    }

    public function removeExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer){
        if(!$this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->removeElement($answer);
        $answer->clearOrder();
    }

    use QRGeneratorTrait;

    public function generateQRCode(): string
    {
        $this->qr_code = $this->generateQRFromFields([
            $this->summit->getOrderQRPrefix(),
            $this->number
        ]);

        return $this->qr_code;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try {
            return is_null($this->owner) ? 0 : $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasCompany():bool{
        return $this->getCompanyId() > 0;
    }

    /**
     * @return int
     */
    public function getCompanyId(){
        try {
            return is_null($this->company) ? 0 : $this->company->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwner():bool{
        return $this->getOwnerId() > 0;
    }

    /**
     * @return float
     */
    public function getRawAmount():float{
        $amount = 0.0;
        foreach ($this->tickets as $ticket){
            $amount += $ticket->getRawCost();
        }
        return $amount;
    }

    /**
     * @return float
     */
    public function getFinalAmount():float {
        $amount = 0.0;
        foreach ($this->tickets as $ticket){
            $amount += $ticket->getFinalAmount();
        }
        return $amount;
    }

    /**
     * @return bool
     */
    public function isFree():bool {
        return $this->getFinalAmount() == 0;
    }

    /**
     * @return bool
     */
    public function hasPaymentInfo():bool{
        return empty($this->payment_gateway_cart_id) || empty($this->payment_gateway_client_token);
    }

    /**
     * @return float
     */
    public function getTaxesAmount(): float{
        $amount = 0.0;
        foreach ($this->tickets as $ticket){
            foreach($ticket->getAppliedTaxes() as $appliedTax){
                $amount += $appliedTax->getAmount();
            }
        }
        return $amount;
    }

    /**
     * @return float
     */
    public function getDiscountAmount(): float{
        $amount = 0.0;
        foreach ($this->tickets as $ticket){
            $amount += $ticket->getDiscount();
        }
        return $amount;
    }

    /**
     * @return string
     */
    public function getCurrency():string{
        $ticket = $this->tickets->first();
        return $ticket->getCurrency();
    }

    /**
     * @return string
     */
    public function getOwnerFullName():string {
        if($this->hasOwner()){
            return $this->owner->getFullName();
        }
        return sprintf("%s %s", $this->owner_first_name, $this->owner_surname);
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
    public function canRefund():bool{
        $validStatuses = [IOrderConstants::RefundRequestedStatus, IOrderConstants::PaidStatus];
        if(!in_array($this->status, $validStatuses)){
            return false;
        }
        if($this->isFree()){
            return false;
        }
        return true;
    }

    /**
     * @param float $amount
     * @throws ValidationException
     */
    public function refund(float $amount)
    {
        if (!$this->canRefund())
            throw new ValidationException
            (
                sprintf
                (
                    "can not request a refund on a %s order",
                    $this->status
                )
            );

        $this->status = IOrderConstants::RefundedStatus;
        $this->refunded_amount = $amount;
        list($tickets_to_return, $promo_codes_to_return) = $this->calculateTicketsAndPromoCodesToReturn();

        foreach ($this->tickets as $ticket){
            $ticket->setRefunded();
        }

        Event::fire(new SummitOrderRefundAccepted($this->getId(), $tickets_to_return, $promo_codes_to_return));
    }

    /**
     * @return string
     * - if tixs in an order are all in a combination of status refund requested or refunded, show order as refund requested.
     * - if all tix in an order are in status refund requested, show order as refund requested.
     * - if all tix in an order are in status refunded, show order as refunded.
     */
    public function recalculateOrderStatus():string {
        Log::debug(sprintf("SummitOrder::recalculateOrderStatus current status %s", $this->status));
        $request_refund_count = 0;
        $refund_count         = 0;

        foreach ($this->tickets as $ticket){
            $ticket_status = $ticket->getStatus();
            Log::debug(sprintf("SummitOrder::recalculateOrderStatus ticket_id %s ticket_status %s", $ticket->getId(), $ticket_status));
            if($ticket_status == IOrderConstants::RefundRequestedStatus)
                ++$request_refund_count;
            if($ticket_status == IOrderConstants::RefundedStatus)
                ++$refund_count;
        }

        $tickets_count = $this->tickets->count();

        Log::debug(sprintf("SummitOrder::recalculateOrderStatus tickets_count %s request_refund_count %s refund_count %s", $tickets_count, $request_refund_count, $refund_count));

        if(($request_refund_count == $tickets_count || ( $refund_count > 0 && $request_refund_count > 0 && ($refund_count + $request_refund_count) == $tickets_count)))
            $this->status = IOrderConstants::RefundRequestedStatus;

        if($refund_count == $tickets_count)
            $this->status = IOrderConstants::RefundedStatus;

        Log::debug(sprintf("SummitOrder::recalculateOrderStatus recalculated status %s", $this->status));

        return $this->status;
    }

    /**
     * @return bool
     */
    public function isRefundRequested():bool {
        return $this->status == IOrderConstants::RefundRequestedStatus;
    }

    /**
     * @return float
     */
    public function getRefundedAmount(): float
    {
        return $this->refunded_amount;
    }

    /**
     * @return \DateTime
     */
    public function getLastReminderEmailSentDate(): ?\DateTime
    {
        $last_action_date = $this->last_reminder_email_sent_date;

        if (is_null($last_action_date)) {
            $last_action_date = $this->getCreatedUTC();
        }

        return $last_action_date;
    }

    /**
     * @param \DateTime $last_reminder_email_sent_date
     */
    public function setLastReminderEmailSentDate(\DateTime $last_reminder_email_sent_date): void
    {
        $this->last_reminder_email_sent_date = $last_reminder_email_sent_date;
    }

    /**
     * @return bool
     */
    public function isSingleOrder():bool{
        if($this->tickets->count() > 1){
            return false;
        }

        $ticket = $this->tickets->first();

        if(!$ticket instanceof SummitAttendeeTicket) return false;

        if($ticket->getOwnerEmail() != $this->getOwnerEmail()) return false;

        return true;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function getFirstTicket():?SummitAttendeeTicket{
        if(is_null($this->tickets)) return null;
        if($this->tickets->count() == 0) return null;
        return $this->tickets->first();
    }

    /*
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

}