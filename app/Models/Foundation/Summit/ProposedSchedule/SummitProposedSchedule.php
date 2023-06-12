<?php namespace App\Models\Foundation\Summit\ProposedSchedule;
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

use App\Models\Foundation\Summit\IPublishableEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\SummitAbstractLocation;
use models\summit\SummitEvent;
use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitProposedScheduleRepository")
 * @ORM\Table(name="SummitProposedSchedule")
 * Class SummitProposedSchedule
 * @package App\Models\Foundation\Summit\ProposedSchedule
 */
class SummitProposedSchedule extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Source", type="string")
     * @var string
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $created_by = null;

    /**
     * @ORM\OneToMany(targetEntity="SummitProposedScheduleSummitEvent", mappedBy="summit_proposed_schedule", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitProposedScheduleSummitEvent[]
     */
    private $scheduled_summit_events;

    public function __construct()
    {
        parent::__construct();
        $this->scheduled_summit_events = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return Member|null
     */
    public function getCreatedBy(): ?Member
    {
        return $this->created_by;
    }

    /**
     * @return bool
     */
    public function hasCreatedBy(){
        return $this->getCreatedById() > 0;
    }

    /**
     * @return int
     */
    public function getCreatedById(){
        try{
            return is_null($this->created_by) ? 0 : $this->created_by->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param Member $created_by
     */
    public function setCreatedBy(Member $created_by): void
    {
        $this->created_by = $created_by;
    }

    /**
     * @return int
     */
    public function getSummitId(){
        try{
            return is_null($this->summit) ? 0 : $this->summit->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return SummitProposedScheduleSummitEvent[]
     */
    public function getScheduledSummitEvents()
    {
        return $this->scheduled_summit_events;
    }

    public function clearScheduledSummitEvents():void
    {
        $this->scheduled_summit_events->clear();
    }

    /**
     * @param int $scheduled_event_id
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getScheduledSummitEventById(int $scheduled_event_id):?SummitProposedScheduleSummitEvent {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $scheduled_event_id));
        $res = $this->scheduled_summit_events->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param int $event_id
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getScheduledSummitEventByEvent(SummitEvent $event):?SummitProposedScheduleSummitEvent {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit_event', $event));
        $res = $this->scheduled_summit_events->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param \DateTime|null $start_date
     * @param \DateTime|null $end_date
     * @param SummitAbstractLocation|null $location
     * @return SummitProposedScheduleSummitEvent[]
     */
    public function getScheduledSummitEventsByLocationAndDateRange(
        ?\DateTime $start_date = null, ?\DateTime $end_date = null, ?SummitAbstractLocation $location = null):array {

        $criteria = Criteria::create();
        if ($start_date != null)
            $criteria->andWhere(Criteria::expr()->gt('end_date', $start_date));
        if ($end_date != null)
            $criteria->andWhere(Criteria::expr()->lt('start_date', $end_date));
        if ($location != null)
            $criteria->andWhere(Criteria::expr()->eq('location', $location));
        return $this->scheduled_summit_events->matching($criteria)->toArray();
    }

    /**
     * @param \DateTime $date
     * @param SummitAbstractLocation|null $location
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getProposedPublishedEventBeforeThan(\DateTime $date, SummitAbstractLocation $location):?SummitProposedScheduleSummitEvent {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->lt('end_date', $date));
        $criteria->andWhere(Criteria::expr()->eq('location', $location));
        $criteria->orderBy(['end_date' => 'DESC']);
        $res = $this->scheduled_summit_events->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param \DateTime $date
     * @param SummitAbstractLocation|null $location
     * @return SummitProposedScheduleSummitEvent|null
     */
    public function getProposedPublishedEventAfterThan(\DateTime $date, SummitAbstractLocation $location):?SummitProposedScheduleSummitEvent {

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->gt('start_date', $date));
        $criteria->andWhere(Criteria::expr()->eq('location', $location));
        $criteria->orderBy(['start_date' => 'ASC']);
        $res = $this->scheduled_summit_events->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    /**
     * @param IPublishableEvent|SummitProposedScheduleSummitEvent $scheduled_event
     * @throws ValidationException
     */
    public function addScheduledSummitEvent(IPublishableEvent $scheduled_event){
        if(!$scheduled_event instanceof SummitProposedScheduleSummitEvent) return;
        if($this->scheduled_summit_events->contains($scheduled_event)) return;

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('summit_event', $scheduled_event->getSummitEvent()));
        if($this->scheduled_summit_events->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Scheduled event %s already exists", $scheduled_event->getId()));

        $this->scheduled_summit_events->add($scheduled_event);
        $scheduled_event->setSchedule($this);
    }

    /**
     * @param IPublishableEvent|SummitProposedScheduleSummitEvent $scheduled_event
     */
    public function removeScheduledSummitEvent(IPublishableEvent $scheduled_event){
        if(!$scheduled_event instanceof SummitProposedScheduleSummitEvent) return;
        if(!$this->scheduled_summit_events->contains($scheduled_event)) return;
        $this->scheduled_summit_events->removeElement($scheduled_event);
        $scheduled_event->clearSchedule();
    }
}