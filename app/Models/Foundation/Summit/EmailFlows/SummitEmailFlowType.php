<?php namespace App\Models\Foundation\Summit\EmailFlows;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitEmailFlowType")
 * Class DefaultTrackTagGroup
 * @package models\summit\DefaultTrackTagGroup
 */
class SummitEmailFlowType extends SilverstripeBaseModel
{
    /**
     * @ORM\OneToMany(targetEntity="SummitEmailEventFlowType", mappedBy="flow", cascade={"persist"}, orphanRemoval=true)
     * @var SummitEmailEventFlowType[]
     */
    private $flow_event_types;

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * SummitEmailFlowType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->flow_event_types = new ArrayCollection();
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
     * @param SummitEmailEventFlowType $event_type
     */
    public function addFlowEventType(SummitEmailEventFlowType $event_type){
        if($this->flow_event_types->contains($event_type)) return;
        $this->flow_event_types->add($event_type);
        $event_type->setFlow($this);
    }

    public function removeFlowEventType(SummitEmailEventFlowType $event_type){
        if(!$this->flow_event_types->contains($event_type)) return;
        $this->flow_event_types->removeElement($event_type);
    }

    public function clearFlowEventType(){
        $this->flow_event_types->clear();
    }

    public function getEventTypes(){
        return $this->flow_event_types;
    }
}