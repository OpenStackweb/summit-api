<?php namespace App\Models\Foundation\Summit;
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
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;

/**
 * @package App\Models\Foundation\Summit
 */
#[ORM\Table(name: 'SelectionPlanAllowedEditablePresentationQuestion')]
#[ORM\Entity]
class SelectionPlanAllowedEditablePresentationQuestion extends SilverstripeBaseModel
{
    /**
     * @var String
     */
    #[ORM\Column(name: 'Type', type: 'string')]
    private $type;

    /**
     * @var SelectionPlan
     */
    #[ORM\JoinColumn(name: 'SelectionPlanID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Summit\SelectionPlan::class, inversedBy: 'allowed_editable_presentation_questions', fetch: 'EXTRA_LAZY')]
    private $selection_plan;

    /**
     * @param SelectionPlan $selection_plan
     * @param string $type
     */
    public function __construct(SelectionPlan $selection_plan, string $type)
    {
        parent::__construct();
        $this->selection_plan = $selection_plan;
        $this->type = trim($type);
    }

    /**
     * @return String
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return SelectionPlan
     */
    public function getSelectionPlan(): SelectionPlan
    {
        return $this->selection_plan;
    }

    public function clearSelectionPlan():void{
        $this->selection_plan = null;
    }
}