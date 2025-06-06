<?php namespace models\summit\CalendarSync\WorkQueue;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit\CalendarSync\WorkQueue
 */
#[ORM\Table(name: 'AbstractCalendarSyncWorkRequest')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineAbstractCalendarSyncWorkRequestRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['AbstractCalendarSyncWorkRequest' => 'AbstractCalendarSyncWorkRequest', 'AdminScheduleSummitActionSyncWorkRequest' => 'AdminScheduleSummitActionSyncWorkRequest', 'AdminSummitEventActionSyncWorkRequest' => 'AdminSummitEventActionSyncWorkRequest', 'AdminSummitLocationActionSyncWorkRequest' => 'AdminSummitLocationActionSyncWorkRequest', 'MemberCalendarScheduleSummitActionSyncWorkRequest' => 'MemberCalendarScheduleSummitActionSyncWorkRequest', 'MemberEventScheduleSummitActionSyncWorkRequest' => 'MemberEventScheduleSummitActionSyncWorkRequest', 'MemberScheduleSummitActionSyncWorkRequest' => 'MemberScheduleSummitActionSyncWorkRequest'])] // Class AbstractCalendarSyncWorkRequest
class AbstractCalendarSyncWorkRequest extends SilverstripeBaseModel
{

    const TypeAdd    = 'ADD';
    const TypeRemove = 'REMOVE';
    const TypeUpdate = 'UPDATE';

    public function __construct()
    {
        parent::__construct();
        $this->is_processed = false;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Type', type: 'string')]
    protected $type;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsProcessed', type: 'boolean', options: ['default' => '0'])]
    protected $is_processed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ProcessedDate', type: 'datetime')]
    protected $processed_date;


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function IsProcessed()
    {
        return $this->is_processed;
    }

    /**
     * @param bool $is_processed
     */
    public function setIsProcessed($is_processed)
    {
        $this->is_processed = $is_processed;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedDate()
    {
        return $this->processed_date;
    }

    /**
     * @param \DateTime $processed_date
     */
    public function setProcessedDate($processed_date)
    {
        $this->processed_date = $processed_date;
    }


    public function markProcessed(){
        $this->is_processed   = true;
        $this->processed_date = new \DateTime('now', new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
    }
}