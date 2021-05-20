<?php namespace App\Models\Foundation\Summit\ExtraQuestions;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Summit\SelectionPlan;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitSelectionPlanExtraQuestionTypeRepository")
 * @ORM\Table(name="SummitSelectionPlanExtraQuestionType")
 * Class SummitSelectionPlanExtraQuestionType
 * @package App\Models\Foundation\Summit\ExtraQuestions
 */
class SummitSelectionPlanExtraQuestionType extends ExtraQuestionType
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="extra_questions")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")
     * @var SelectionPlan
     */
    protected $selection_plan;

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

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSelectionPlanId' => 'selection_plan',
    ];

    protected $hasPropertyMappings = [
        'hasSelectionPlan' => 'selection_plan',
    ];

    public function clearSelectionPlan():void{
        $this->selection_plan = null;
    }
}