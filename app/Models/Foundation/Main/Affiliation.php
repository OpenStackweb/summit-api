<?php namespace models\main;
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
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\main
 */
#[ORM\Table(name: 'Affiliation')]
#[ORM\Entity]
class Affiliation extends SilverstripeBaseModel
{
    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'StartDate', type: 'datetime')]
    private $start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'EndDate', type: 'datetime')]
    private $end_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Current', type: 'boolean')]
    private $is_current;

    /**
     * @var string
     */
    #[ORM\Column(name: 'JobTitle', type: 'string')]
    private $job_title;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'affiliations')]
    private $owner;

    /**
     * @var Organization
     */
    #[ORM\JoinColumn(name: 'OrganizationID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Organization::class)]
    private $organization;

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param \DateTime $start_date
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        return $this->is_current;
    }

    public function getIsCurrent(){
        return $this->isCurrent();
    }

    /**
     * @param bool $is_current
     */
    public function setIsCurrent($is_current)
    {
        $this->is_current = $is_current;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function clearOwner(){
        $this->owner = null;
    }

    /**
     * @param Member $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try {
            return $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getOrganizationId(){
        try {
            return $this->organization->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOrganization(){
        return $this->getOrganizationId() > 0;
    }

    /**
     * @return mixed
     */
    public function getJobTitle()
    {
        return $this->job_title;
    }

    /**
     * @param mixed $job_title
     */
    public function setJobTitle($job_title)
    {
        $this->job_title = $job_title;
    }

    public function clearEndDate(){
        $this->end_date = null;
    }

    public function clearStartDate(){
        $this->start_date = null;
    }

    public function __construct()
    {
        parent::__construct();
        $this->is_current = false;
        $this->start_date = null;
        $this->end_date = null;
        $this->organization = null;
        $this->owner = null;
    }
}