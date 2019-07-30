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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitBookableVenueRoom")
 * Class SummitBookableVenueRoom
 * @package models\summit
 */
class SummitBookableVenueRoom extends SummitVenueRoom
{

    const ClassName = 'SummitBookableVenueRoom';

    /**
     * @ORM\Column(name="TimeSlotCost", type="integer")
     * @var int
     */
    private $time_slot_cost;

    /**
     * @var string
     * @ORM\Column(name="Currency", type="string")
     */
    private $currency;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitRoomReservation", mappedBy="room", cascade={"persist"}, orphanRemoval=true)
     * @var ArrayCollection
     */
    private $reservations;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitBookableVenueRoomAttributeValue")
     * @ORM\JoinTable(name="SummitBookableVenueRoom_Attributes",
     *      joinColumns={@ORM\JoinColumn(name="SummitBookableVenueRoomID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitBookableVenueRoomAttributeValueID", referencedColumnName="ID", unique=true)}
     *      )
     */
    private $attributes;

    public function __construct()
    {
        parent::__construct();
        $this->reservations = new ArrayCollection();
        $this->attributes   = new ArrayCollection();
    }

    /**
     * @param SummitRoomReservation $reservation
     * @return $this
     * @throws ValidationException
     */
    public function addReservation(SummitRoomReservation $reservation){

        if(!$this->summit->isBookingPeriodOpen())
            throw new ValidationException(sprintf("booking period is not open for summit %s", $this->summit->getId()));

        $criteria         = Criteria::create();
        $start_date       = $reservation->getStartDatetime();
        $end_date         = $reservation->getEndDatetime();
        $summit           = $this->summit;
        $local_start_date = $summit->convertDateFromUTC2TimeZone(clone $start_date);
        $local_end_date   = $summit->convertDateFromUTC2TimeZone(clone $end_date);

        if(!$summit->isTimeFrameOnBookingPeriod($local_start_date, $local_end_date))
            throw new ValidationException("requested reservation slot does not belong to summit booking period");

        $criteria
            ->where(Criteria::expr()->eq('start_datetime', $start_date))
            ->andWhere(Criteria::expr()->eq('end_datetime',$end_date))
            ->andWhere(Criteria::expr()->notIn("status", [
                SummitRoomReservation::RequestedRefundStatus,
                SummitRoomReservation::RefundedStatus,
                SummitRoomReservation::Canceled
            ]));

        if($this->reservations->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("reservation overlaps an existent reservation (1)"));

        $criteria
            ->where(Criteria::expr()->lt('start_datetime', $end_date))
            ->andWhere(Criteria::expr()->gt('end_datetime', $start_date))
            ->andWhere(Criteria::expr()->notIn("status", [
                SummitRoomReservation::RequestedRefundStatus,
                SummitRoomReservation::RefundedStatus,
                SummitRoomReservation::Canceled
            ]));

        if($this->reservations->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("reservation overlaps an existent reservation (2)"));

        $start_time       = $summit->getMeetingRoomBookingStartTime();
        $end_time         = $summit->getMeetingRoomBookingEndTime();

        $local_start_time = new \DateTime("now", $this->summit->getTimeZone());
        $local_start_time->setTime(
            intval($start_time->format("H")),
            intval($start_time->format("i")),
            intval($start_time->format("s"))
        );

        $local_end_time = new \DateTime("now", $this->summit->getTimeZone());
        $local_end_time->setTime(
            intval($end_time->format("H")),
            intval($end_time->format("i")),
            intval($end_time->format("s"))
        );

        $local_start_time->setDate
        (
            intval($local_start_date->format("Y")),
            intval($local_start_date->format("m")),
            intval($local_start_date->format("d"))
        );

        $local_end_time->setDate
        (
            intval($local_start_date->format("Y")),
            intval($local_start_date->format("m")),
            intval($local_start_date->format("d"))
        );

        if(!($local_start_time <= $local_start_date
        && $local_end_date <= $local_end_time))
            throw new ValidationException
            (
                sprintf
                (
                    "requested booking time slot is not allowed! requested [from %s to %s] allowed [from %s to %s]",
                    $local_start_date->format("Y-m-d H:i:s"),
                    $local_end_date->format("Y-m-d H:i:s"),
                    $local_start_time->format("Y-m-d H:i:s"),
                    $local_end_time->format("Y-m-d H:i:s")
                )
            );

        $interval = $end_date->diff($start_date);
        $minutes  =  ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;
        if($minutes != $summit->getMeetingRoomBookingSlotLength())
            throw new ValidationException
            (
                sprintf
                (
                    "requested booking time slot is not allowed! request slot (%s minutes) - summit allowed slot (%s minutes)",
                    $minutes,
                    $summit->getMeetingRoomBookingSlotLength()
                )
            );

        $now_utc    = new \DateTime('now', new \DateTimeZone('UTC'));
        // we cant choose the slots on the past or slots that are going on
        if((($now_utc > $end_date) || ( $start_date <= $now_utc && $now_utc <= $end_date ))){
            throw new ValidationException("selected slot is on the past");
        }

        $this->reservations->add($reservation);
        $reservation->setRoom($this);
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeSlotCost(): int
    {
        return $this->time_slot_cost;
    }

    /**
     * @param int $time_slot_cost
     */
    public function setTimeSlotCost(int $time_slot_cost): void
    {
        $this->time_slot_cost = $time_slot_cost;
    }

    /**
     * @return ArrayCollection
     */
    public function getReservations(): ArrayCollection
    {
        return $this->reservations;
    }

    /**
     * @param int $id
     * @return SummitRoomReservation|null
     */
    public function getReservationById(int $id):?SummitRoomReservation{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($id)));
        $reservation = $this->reservations->matching($criteria)->first();
        return $reservation === false ? null : $reservation;
    }

    public function clearReservations(){
        $this->reservations->clear();
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return self::ClassName;
    }

    /**
     * @param \DateTime $day should be on local summit day
     * @return array
     * @throws ValidationException
     */
    public function getAvailableSlots(\DateTime $day): array {
        $availableSlots     = [];
        $summit             = $this->summit;
        $test_date          = clone $day;
        // reset time only interest the date portion
        $test_date          = $test_date->setTimezone($summit->getTimeZone())->setTime(0, 0,0);

        $booking_start_time = $summit->getMeetingRoomBookingStartTime();
        if(is_null($booking_start_time))
            throw new ValidationException("MeetingRoomBookingStartTime is null!");

        $booking_end_time   = $summit->getMeetingRoomBookingEndTime();
        if(is_null($booking_end_time))
            throw new ValidationException("MeetingRoomBookingEndTime is null!");

        $booking_slot_len     = $summit->getMeetingRoomBookingSlotLength();
        $local_start_datetime = clone $test_date;
        $local_end_datetime   = clone $test_date;

        // set the time frames

        $local_start_datetime->setTimezone($summit->getTimeZone())->setTime(
            intval($booking_start_time->format("H")),
            intval($booking_start_time->format("i")),
            0);

        $local_end_datetime->setTimezone($summit->getTimeZone())->setTime(
            intval($booking_end_time->format("H")),
            intval($booking_end_time->format("i")),
            00);

        // check if day belongs to booking period

        if(!$summit->isTimeFrameOnBookingPeriod($local_start_datetime, $local_end_datetime))
            throw new ValidationException("requested day does not belong to summit booking period");

        // now we have the allowed time frame for that particular day

        $criteria = Criteria::create();

        $criteria
            ->where(Criteria::expr()->gte('start_datetime', $summit->convertDateFromTimeZone2UTC($local_start_datetime)))
            ->andWhere(Criteria::expr()->lte('end_datetime', $summit->convertDateFromTimeZone2UTC($local_end_datetime)))
            ->andWhere(Criteria::expr()->notIn("status", [
                SummitRoomReservation::RequestedRefundStatus,
                SummitRoomReservation::RefundedStatus,
                SummitRoomReservation::Canceled,
            ]));

        $reservations   = $this->reservations->matching($criteria);
        // calculate all possible slots
        while($local_start_datetime <= $local_end_datetime) {
            $current_time_slot_end = clone $local_start_datetime;
            $current_time_slot_end->add(new \DateInterval("PT" . $booking_slot_len . 'M'));
            if($current_time_slot_end <= $local_end_datetime)
                $availableSlots[$local_start_datetime->format('Y-m-d H:i:s').'|'.$current_time_slot_end->format('Y-m-d H:i:s')] = true;
            $local_start_datetime = $current_time_slot_end;
        }

        // and mark all not available slots on that time frame
        foreach ($reservations as $reservation){
            if(!$reservation instanceof SummitRoomReservation) continue;
            $availableSlots[
                $summit->convertDateFromUTC2TimeZone($reservation->getStartDatetime())->format("Y-m-d H:i:s")
                .'|'.
                $summit->convertDateFromUTC2TimeZone($reservation->getEndDatetime())->format("Y-m-d H:i:s")
            ] = false;
        }

        return $availableSlots;
    }

    /**
     * @param \DateTime $day
     * @return array
     * @throws ValidationException
     */
    public function getFreeSlots(\DateTime $day):array{

        $slots      = $this->getAvailableSlots(clone $day);
        $free_slots = [];

        foreach ($slots as $label => $status){
            if(!$status) continue;
            $free_slots[] = $label;
        }
        return $free_slots;
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
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param SummitBookableVenueRoomAttributeValue $attribute
     */
    public function addAttribute(SummitBookableVenueRoomAttributeValue $attribute){
        if($this->attributes->contains($attribute)) return;
        $this->attributes->add($attribute);
    }

    /**
     * @param SummitBookableVenueRoomAttributeValue $attribute
     */
    public function removeAttribute(SummitBookableVenueRoomAttributeValue $attribute){
        if(!$this->attributes->contains($attribute)) return;
        $this->attributes->removeElement($attribute);
    }

}