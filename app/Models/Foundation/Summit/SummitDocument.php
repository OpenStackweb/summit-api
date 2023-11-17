<?php namespace models\summit;
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

use App\Models\Foundation\Summit\SelectionPlan;
use Doctrine\ORM\Mapping AS ORM;
use models\main\File;
use Doctrine\Common\Collections\ArrayCollection;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitDocumentRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="summit_documents"
 *     )
 * })
 * @ORM\Table(name="SummitDocument")
 * Class SummitDocument
 * @package models\summit
 */
class SummitDocument extends SilverstripeBaseModel
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSelectionPlanId' => 'selection_plan',
    ];

    protected $hasPropertyMappings = [
        'hasSelectionPlan' => 'selection_plan',
    ];
    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @ORM\Column(name="ShowAlways", type="boolean")
     * @var bool
     */
    private $show_always;

    /**
     * @ORM\Column(name="WebLink", type="string")
     * @var string
     */
    private $web_link;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")
     * @var SelectionPlan
     */
    private $selection_plan;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="FileID", referencedColumnName="ID")
     * @var File
     */
    private $file;

    /**
     * @ORM\ManyToMany(targetEntity="SummitEventType", inversedBy="summit_documents")
     * @ORM\JoinTable(name="SummitDocument_EventTypes",
     *      joinColumns={@ORM\JoinColumn(name="SummitDocumentID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitEventTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitEventType[]
     */
    private $event_types;

    /**
     * SummitDocument constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->event_types = new ArrayCollection();
        $this->file = null;
        $this->label = '';
        $this->description = '';
        $this->name = '';
        $this->show_always = false;
        $this->web_link = null;
    }

    /**
     * @return bool
     */
    public function hasFile(){
        return $this->getFileId() > 0;
    }

    /**
     * @return int
     */
    public function getFileId(){
        try{
            return !is_null($this->file) ? $this->file->getId():0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return File
     */
    public function getFile():?File
    {
        return $this->file;
    }

    public function clearFile(){
        $this->file = null;
    }

    /**
     * @param File $file
     */
    public function setFile(File $file)
    {
        //clear web_link as it's no longer required when a file is added
        $this->web_link = null;
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getName(): ?string
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
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getEventTypes(){
        return $this->event_types;
    }

    public function addEventType(SummitEventType $eventType){
        if($this->event_types->contains($eventType)) return;
        $this->event_types->add($eventType);
    }

    public function removeEventType(SummitEventType $eventType){
        if(!$this->event_types->contains($eventType)) return;
        $this->event_types->removeElement($eventType);
    }

    public function clearEventTypes(){
        $this->event_types->clear();
    }

    /**
     * @return string|null
     */
    public function getFileUrl():?string{
        $fileUrl = null;
        if($this->hasFile() && $file = $this->getFile()){
            $fileUrl =  $file->getUrl();
        }
        return $fileUrl;
    }

    /**
     * @return bool
     */
    public function isShowAlways(): bool
    {
        return $this->show_always;
    }

    /**
     * @param bool $show_always
     */
    public function setShowAlways(bool $show_always): void
    {
        $this->show_always = $show_always;
        if($this->show_always){
            $this->clearEventTypes();
        }
    }

    /**
     * @return string
     */
    public function getWebLink(): ?string
    {
        return $this->web_link;
    }

    /**
     * @param string $web_link
     */
    public function setWebLink(string $web_link): void
    {
        $this->web_link = $web_link;
    }

    /**
     * @return SelectionPlan|null
     */
    public function getSelectionPlan(): ?SelectionPlan
    {
        return $this->selection_plan;
    }

    /**
     * @param SelectionPlan $selection_plan
     */
    public function setSelectionPlan(SelectionPlan $selection_plan): void
    {
        $this->selection_plan = $selection_plan;
    }

    public function clearSelectionPlan():void{
        $this->selection_plan = null;
    }

}