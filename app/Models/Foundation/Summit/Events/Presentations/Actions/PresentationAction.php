<?php namespace models\summit;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationAction")
 * Class PresentationAction
 * @package models\summit
 */
class PresentationAction extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;
    /**
     * @ORM\Column(name="IsCompleted", type="boolean")
     * @var boolean
     */
    private $is_completed;

    protected $getIdMappings = [
        'getPresentationId' => 'presentation',
        'getTypeId' => 'type',
        'getCreatedById' => 'created_by',
        'getUpdatedById' => 'updated_by',
    ];

    protected $hasPropertyMappings = [
        'hasPresentation' => 'presentation',
        'hasType' => 'type',
        'hasCreatedBy' => 'created_by',
        'hasUpdatedBy' => 'updated_by',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->is_completed = false;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Presentation", inversedBy="actions", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID", onDelete="CASCADE")
     * @var Presentation
     */
    private $presentation;

    /**
     * @ORM\ManyToOne(targetEntity="PresentationActionType", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID", onDelete="CASCADE")
     * @var PresentationActionType
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $created_by;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="UpdateByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $updated_by;

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    /**
     * @param bool $is_completed
     */
    public function setIsCompleted(bool $is_completed): void
    {
        $this->is_completed = $is_completed;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    /**
     * @return PresentationActionType
     */
    public function getType(): PresentationActionType
    {
        return $this->type;
    }

    /**
     * @param PresentationActionType $type
     */
    public function setType(PresentationActionType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Member
     */
    public function getCreatedBy(): ?Member
    {
        return $this->created_by;
    }

    /**
     * @param Member $created_by
     */
    public function setCreatedBy(Member $created_by): void
    {
        $this->created_by = $created_by;
    }

    /**
     * @return Member
     */
    public function getUpdatedBy(): ?Member
    {
        return $this->updated_by;
    }

    /**
     * @param Member $updated_by
     */
    public function setUpdatedBy(Member $updated_by): void
    {
        $this->updated_by = $updated_by;
    }

    public function __toString():string
    {
        return sprintf("%s : %s", strip_tags($this->type->getLabel()), $this->is_completed ? "ON":"OFF");
    }


}