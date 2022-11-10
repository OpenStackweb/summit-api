<?php namespace App\Models\Foundation\Summit\ExtraQuestions;
/*
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
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitSelectionPlanExtraQuestionType_SelectionPlan")
 * Class AssignedSelectionPlanExtraQuestionType
 * @package App\Models\Foundation\Summit\ExtraQuestions
 */
class AssignedSelectionPlanExtraQuestionType
    extends BaseEntity
    implements IOrderable
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType", inversedBy="assigned_selection_plans")
     * @ORM\JoinColumn(name="SummitSelectionPlanExtraQuestionTypeID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitSelectionPlanExtraQuestionType
     */
    private $question_type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="extra_questions")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SelectionPlan
     */
    private $selection_plan;

    /**
     * @ORM\Column(name="`CustomOrder`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @return SelectionPlan
     */
    public function getSelectionPlan(): SelectionPlan
    {
        return $this->selection_plan;
    }

    /**
     * @return SummitSelectionPlanExtraQuestionType
     */
    public function getQuestionType():SummitSelectionPlanExtraQuestionType{
        return $this->question_type;
    }

    public function setQuestionType(SummitSelectionPlanExtraQuestionType $question):void{
        $this->question_type = $question;
    }

    /**
     * @param SelectionPlan $selection_plan
     */
    public function setSelectionPlan(SelectionPlan $selection_plan): void
    {
        $this->selection_plan = $selection_plan;
    }

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSelectionPlanId' => 'selection_plan',
        'getQuestionTypeId' => 'question_type'
    ];

    protected $hasPropertyMappings = [
        'hasSelectionPlan' => 'selection_plan',
        'hasQuestionType' => 'question_type',
    ];

    public function clearSelectionPlan():void{
        $this->selection_plan = null;
        $this->question_type = null;
    }

    public function __construct()
    {
        $this->order = 1;
    }

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
}