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
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrinePresentationTrackChairScoreTypeRepository")
 * @ORM\Table(name="PresentationTrackChairScoreType")
 * Class PresentationTrackChairScoreType
 * @package App\Models\Foundation\Summit\Events\Presentations\TrackChairs
 */
class PresentationTrackChairScoreType
    extends SilverstripeBaseModel
    implements IOrderable
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTypeId' => 'type',
    ];

    protected $hasPropertyMappings = [
        'hasType' => 'type',
    ];

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="`Score`", type="integer")
     * @var int
     */
    private $score;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType", inversedBy="score_types", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID")
     * @var PresentationTrackChairRatingType
     */
    private $type;

    public function __construct()
    {
        parent::__construct();
        $this->score = 1;
    }

    /**
     * @param int $order
     * @return void
     */
    public function setOrder($order)
    {
        $this->setScore($order);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->getScore();
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
     * @return string
     */
    public function getDescription(): string
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
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     * @throws ValidationException
     */
    public function setScore(int $score): void
    {
        if($score <= 0)
            throw new ValidationException("Score should be greater than zero.");
        $this->score = $score;
    }

    /**
     * @return PresentationTrackChairRatingType
     */
    public function getType(): PresentationTrackChairRatingType
    {
        return $this->type;
    }

    /**
     * @param PresentationTrackChairRatingType $type
     */
    public function setType(PresentationTrackChairRatingType $type): void
    {
        $this->type = $type;
    }

    public function clearType():void{
        $this->type = null;
    }
}