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
use App\Events\BookableRoomReservationCanceled;
use App\Events\BookableRoomReservationRefundAccepted;
use App\Events\PaymentBookableRoomReservationConfirmed;
use App\Events\RequestedBookableRoomReservationRefund;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitRoomReservationRepository")
 * @ORM\Table(name="SummitRoomReservation")
 * Class SummitRoomReservation
 * @package models\summit
 */
class SummitRoomReservation extends SilverstripeBaseModel
{
    /**
     * @var \DateTime
     * @ORM\Column(name="StartDateTime", type="datetime")
     */
    private $start_datetime;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDateTime", type="datetime")
     */
    private $end_datetime;

    /**
     * @var \DateTime
     * @ORM\Column(name="ApprovedPaymentDate", type="datetime")
     */
    private $approved_payment_date;

    /**
     * @var string
     * @ORM\Column(name="Status", type="string")
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="LastError", type="string")
     */
    private $last_error;

    /**
     * @var string
     * @ORM\Column(name="PaymentGatewayCartId", type="string")
     */
    private $payment_gateway_cart_id;

    /**
     * @var string
     * @ORM\Column(name="PaymentGatewayClientToken", type="string")
     */
    private $payment_gateway_client_token;

    /**
     * @var string
     * @ORM\Column(name="Currency", type="string")
     */
    private $currency;

    /**
     * @var float
     * @ORM\Column(name="Amount", type="integer")
     */
    private $amount;

    /**
     * @var float
     * @ORM\Column(name="RefundedAmount", type="integer")
     */
    private $refunded_amount;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="reservations")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID")
     * @var Member
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitBookableVenueRoom", inversedBy="reservations")
     * @ORM\JoinColumn(name="RoomID", referencedColumnName="ID")
     * @var SummitBookableVenueRoom
     */
    private $room;

    const ReservedStatus         = "Reserved";
    const ErrorStatus            = "Error";
    const PaidStatus             = "Paid";
    const RequestedRefundStatus  = "RequestedRefund";
    const RefundedStatus         = "Refunded";
    const Canceled               = "Canceled";

    public static $valid_status = [
        self::ReservedStatus,
        self::PaidStatus,
        self::RequestedRefundStatus,
        self::RefundedStatus,
        self::ErrorStatus,
        self::Canceled
    ];

    /**
     * @return \DateTime
     */
    public function getStartDatetime(): \DateTime
    {
        return $this->start_datetime;
    }

    /**
     * @param int $amount
     */
    public function refund(int $amount){
        $this->status = self::RefundedStatus;
        $this->refunded_amount = $amount;
        Event::dispatch(new BookableRoomReservationRefundAccepted($this->getId()));
    }

    /**
     * @return \DateTime
     */
    public function getLocalStartDatetime(): \DateTime
    {
        return $this->room->getSummit()->convertDateFromUTC2TimeZone($this->start_datetime);
    }

    /**
     * @param \DateTime $start_datetime
     */
    public function setStartDatetime(\DateTime $start_datetime): void
    {
        $this->start_datetime = $start_datetime;
    }

    /**
     * @return \DateTime
     */
    public function getEndDatetime(): \DateTime
    {
        return $this->end_datetime;
    }

    /**
     * @return \DateTime
     */
    public function getLocalEndDatetime(): \DateTime
    {
        return $this->room->getSummit()->convertDateFromUTC2TimeZone($this->end_datetime);
    }

    /**
     * @param \DateTime $end_datetime
     */
    public function setEndDatetime(\DateTime $end_datetime): void
    {
        $this->end_datetime = $end_datetime;
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
     * @return Member
     */
    public function getOwner(): Member
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
     * @return SummitBookableVenueRoom
     */
    public function getRoom(): SummitBookableVenueRoom
    {
        return $this->room;
    }

    /**
     * @param SummitBookableVenueRoom $room
     */
    public function setRoom(SummitBookableVenueRoom $room): void
    {
        $this->room = $room;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayCartId(): string
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
     * @return \DateTime|null
     */
    public function getApprovedPaymentDate(): ?\DateTime
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
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function __construct()
    {
        parent::__construct();
        $this->amount = 0;
        $this->refunded_amount = 0;
        $this->status = self::ReservedStatus;
    }

    /**
     * @return string|null
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
     * @return bool
     */
    public function isPaid():bool {
        return $this->status == self::PaidStatus;
    }

    public function setPaid():void{
        if($this->isPaid()){
            Log::warning(sprintf("SummitRoomReservation %s is already Paid", $this->getId()));
            return;
        }

        if($this->status != self::ReservedStatus){
            Log::warning(sprintf("setting payed status to SummitRoomReservation %s with status %s", $this->getId(), $this->status));
        }

        $this->status = self::PaidStatus;
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->approved_payment_date = $now;
        Event::dispatch(new PaymentBookableRoomReservationConfirmed($this->getId()));
    }

    public function cancel():void{
        $this->status = self::Canceled;
        Event::dispatch(new BookableRoomReservationCanceled($this->id));
    }

    public function requestRefund():void{
        $this->status = self::RequestedRefundStatus;
        Event::dispatch(new RequestedBookableRoomReservationRefund($this->getId()));
    }

    /**
     * @param null|string $error
     */
    public function setPaymentError(?string $error):void{
        if(empty($error)) return;
        $this->status = self::ErrorStatus;
        $this->last_error = $error;
    }

    /**
     * @return null|string
     */
    public function getLastError():?string{
        return $this->last_error;
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
     * @return int
     */
    public function getRoomId(){
        try {
            return is_null($this->room) ? 0 : $this->room->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getRefundedAmount(): int
    {
        return $this->refunded_amount;
    }

}