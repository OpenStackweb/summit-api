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
use App\Events\PresentationActionTypeCreated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Foundation\Main\IOrderable;
use Illuminate\Support\Facades\Event;
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
    implements IOrderable
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\AllowedPresentationActionType", mappedBy="type", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var AllowedPresentationActionType[]
     */
    private $selection_plans;

    /**
     * PresentationActionType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order = 1;
        $this->selection_plans = new ArrayCollection;
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
     * @ORM\PostPersist
     */
    public function inserted($args)
    {
        Event::dispatch(new PresentationActionTypeCreated($this));
    }

    /**
     * @return ArrayCollection
     */
    public function getSelectionPlans(): ArrayCollection {
        return $this->selection_plans->map(function ($entity) {
            return $entity->getSelectionPlan();
        });
    }
}