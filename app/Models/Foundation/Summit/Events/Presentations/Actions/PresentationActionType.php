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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrinePresentationActionTypeRepository")
 * @ORM\Table(name="PresentationActionType")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="presentation_action_types"
 *     )
 * })
 * @ORM\HasLifecycleCallbacks
 * Class PresentationActionType
 * @package models\summit
 */
class PresentationActionType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @deprecated
     * @var int
     */
    private $order;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\AllowedPresentationActionType", mappedBy="type", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var AllowedPresentationActionType[]
     */
    private $assigned_selection_plans;

    /**
     * PresentationActionType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->assigned_selection_plans = new ArrayCollection;
    }

    /**
     * @return string
     */
    public function getLabel(): string
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

    /**
     * @return ArrayCollection
     */
    public function getSelectionPlanAssignmentOrder(int $selection_plan_id): int {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('selection_plan_id', $selection_plan_id));
        $assigned_selection_plan = $this->assigned_selection_plans->matching($criteria)->first();
        return $assigned_selection_plan === false ? 0 : $assigned_selection_plan->getOrder();
    }

    /**
     * @return ArrayCollection
     */
    public function getSelectionPlans(): ArrayCollection {
        return $this->assigned_selection_plans->map(function ($entity) {
            return $entity->getSelectionPlan();
        });
    }
}