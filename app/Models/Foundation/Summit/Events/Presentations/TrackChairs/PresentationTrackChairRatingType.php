<?php namespace App\Models\Foundation\Summit\Events\Presentations\TrackChairs;
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
use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\SelectionPlan;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrinePresentationTrackChairRatingTypeRepository")
 * @ORM\Table(name="PresentationTrackChairRatingType")
 * Class PresentationTrackChairRatingType
 * @package App\Models\Foundation\Summit\Events\Presentations\TrackChairs
 */
class PresentationTrackChairRatingType
    extends SilverstripeBaseModel
    implements IOrderable
{

    use One2ManyPropertyTrait;

    use OrderableChilds;

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
     * @ORM\Column(name="Weight", type="float")
     * @var float
     */
    private $weight;

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="track_chair_rating_types", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")
     * @var SelectionPlan
     */
    private $selection_plan;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType", mappedBy="type", cascade={"persist","remove"}, orphanRemoval=true)
     * @var
     */
    private $score_types;

    public function __construct()
    {
        parent::__construct();
        $this->weight = 0.0;
        $this->order = 1 ;
        $this->score_types = new ArrayCollection();
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
    public function getName(): string
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
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
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

    /**
     * @return ArrayCollection|PresentationTrackChairScoreType[]
     */
    public function getScoreTypes()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['score' => 'ASC']);
        return $this->score_types->matching($criteria);
    }

    /**
     * @return ?PresentationTrackChairScoreType
     */
    public function getScoreTypeById(int $id): ?PresentationTrackChairScoreType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->score_types->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @return int
     */
    private function getScoreTypeMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['score' => 'DESC']);
        $score = $this->score_types->matching($criteria)->first();
        return $score === false ? 0 : $score->getOrder();
    }

    /**
     * @param PresentationTrackChairScoreType $score
     */
    public function addScoreType(PresentationTrackChairScoreType $score):void
    {
        if ($this->score_types->contains($score)) return;
        $score->setOrder($this->getScoreTypeMaxOrder() + 1);
        $this->score_types->add($score);
        $score->setType($this);
    }

    /**
     * @param PresentationTrackChairScoreType $score
     */
    public function removeScoreType(PresentationTrackChairScoreType $score):void
    {
        if (!$this->score_types->contains($score)) return;
        $score->setOrder($this->getScoreTypeMaxOrder() + 1);
        $this->score_types->removeElement($score);
        $score->clearType();
        self::resetOrderForSelectable($this->score_types);
    }

    public function clearScoreTypes():void{
        $this->score_types->clear();
    }

    public function clearSelectionPlan():void{
        $this->selection_plan = null;
    }

}