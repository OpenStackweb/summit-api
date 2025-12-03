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
 * @package models\summit
 */
#[ORM\Table(name: 'SummitDocument')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitDocumentRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'summit_documents')])]
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
    #[ORM\Column(name: 'ClassName', type: 'string')]
    private $class_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Label', type: 'string')]
    private $label;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'ShowAlways', type: 'boolean')]
    private $show_always;

    /**
     * @var string
     */
    #[ORM\Column(name: 'WebLink', type: 'string')]
    private $web_link;

    /**
     * @var SelectionPlan
     */
    #[ORM\JoinColumn(name: 'SelectionPlanID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Summit\SelectionPlan::class)]
    private $selection_plan;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'FileID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist', 'remove'])]
    private $file;

    /**
     * @var SummitEventType[]
     */
    #[ORM\JoinTable(name: 'SummitDocument_EventTypes')]
    #[ORM\JoinColumn(name: 'SummitDocumentID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitEventTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitEventType::class, inversedBy: 'summit_documents')]
    private $event_types;

    /**
     * SummitDocument constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->class_name = 'SummitDocument';
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