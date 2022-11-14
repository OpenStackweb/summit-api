<?php namespace models\summit;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use models\utils\One2ManyPropertyTrait;
/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationActionType_SelectionPlan")
 * Class AllowedPresentationActionType
 * @package models\summit
 */
class AllowedPresentationActionType extends BaseEntity implements IOrderable
{
    use One2ManyPropertyTrait;
    /**
     * @ORM\Column(name="CustomOrder", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\Column(name="PresentationActionTypeID", type="integer")
     * @var int
     */
    private $type_id;

    /**
     * @ORM\Column(name="SelectionPlanID", type="integer")
     * @var int
     */
    private $selection_plan_id;

    protected $getIdMappings = [
        'getTypeId' => 'type',
        'getSelectionPlanId' => 'selection_plan',
    ];

    protected $hasPropertyMappings = [
        'hasType' => 'type',
        'hasSelectionPlan' => 'selection_plan',
    ];

    /**
     * @param PresentationActionType $type
     * @param SelectionPlan $selection_plan
     * @param int $order
     */
    public function __construct(PresentationActionType $type, SelectionPlan $selection_plan, int $order)
    {
        $this->type = $type;
        $this->selection_plan = $selection_plan;
        $this->order = $order;
    }

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationActionType", inversedBy="assigned_selection_plans", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="PresentationActionTypeID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationActionType
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="allowed_presentation_action_types", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SelectionPlan
     */
    private $selection_plan;

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
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
     * @return SelectionPlan
     */
    public function getSelectionPlan(): SelectionPlan
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
}