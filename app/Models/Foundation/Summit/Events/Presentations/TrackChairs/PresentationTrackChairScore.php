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
use Doctrine\ORM\Mapping AS ORM;
use models\summit\Presentation;
use models\summit\SummitTrackChair;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Summit\Events\Presentations\TrackChairs
 */
#[ORM\Table(name: 'PresentationTrackChairScore')]
#[ORM\Entity]
class PresentationTrackChairScore
    extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTypeId' => 'type',
        'getReviewerId' => 'reviewer',
        'getPresentationId' => 'presentation',
    ];

    protected $hasPropertyMappings = [
        'hasType' => 'type',
        'hasReviewer' => 'reviewer',
        'hasPresentation' => 'presentation',
    ];

    /**
     * @var PresentationTrackChairScoreType
     */
    #[ORM\JoinColumn(name: 'TypeID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType::class)]
    private $type;

    /**
     * @var SummitTrackChair
     */
    #[ORM\JoinColumn(name: 'TrackChairID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitTrackChair::class, inversedBy: 'scores')]
    private $reviewer;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Presentation::class, inversedBy: 'track_chairs_scores')]
    private $presentation;

    /**
     * @return PresentationTrackChairScoreType
     */
    public function getType(): PresentationTrackChairScoreType
    {
        return $this->type;
    }

    /**
     * @param PresentationTrackChairScoreType $type
     */
    public function setType(PresentationTrackChairScoreType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return SummitTrackChair
     */
    public function getReviewer(): SummitTrackChair
    {
        return $this->reviewer;
    }

    /**
     * @param SummitTrackChair $reviewer
     */
    public function setReviewer(SummitTrackChair $reviewer): void
    {
        $this->reviewer = $reviewer;
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

    public function clearReviewer():void{
        $this->reviewer = null;
    }

    public function clearPresentation():void{
        $this->presentation = null;
    }
}