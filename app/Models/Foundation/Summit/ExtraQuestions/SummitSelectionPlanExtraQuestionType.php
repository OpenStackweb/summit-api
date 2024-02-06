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
use Doctrine\Common\Collections\ArrayCollection;
use models\summit\SummitOwned;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitSelectionPlanExtraQuestionTypeRepository")
 * @ORM\Table(name="SummitSelectionPlanExtraQuestionType")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="selection_plan_extra_questions"
 *     )
 * })
 * Class SummitSelectionPlanExtraQuestionType
 * @package App\Models\Foundation\Summit\ExtraQuestions
 */
class SummitSelectionPlanExtraQuestionType extends ExtraQuestionType
{
    use SummitOwned;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType", mappedBy="question_type",  cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var AssignedSelectionPlanExtraQuestionType[]
     */
    private $assigned_selection_plans;

    public function __construct()
    {
        parent::__construct();
        $this->assigned_selection_plans = new ArrayCollection();
    }

    public function getAssignments(){
        return $this->assigned_selection_plans;
    }

    public function hasAssignedPlans():bool{
        return $this->assigned_selection_plans->count() > 0;
    }

    /**
     * @param int $selection_plan_id
     * @return int|null
     */
    public function getOrderByAssignedSelectionPlan(int $selection_plan_id):?int{
        $value = $this->assigned_selection_plans->filter(function($e) use($selection_plan_id){
            return $e->getSelectionPlan()->getId() === $selection_plan_id;
        })->first();
        return $value === false ? null : $value->getOrder();
    }

}