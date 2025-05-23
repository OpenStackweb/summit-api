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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
use models\utils\One2ManyPropertyTrait;
/**
 * @package models\summit;
 */
#[ORM\Table(name: 'PresentationAttendeeVote')]
#[ORM\Entity]
class PresentationAttendeeVote extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getVoterId' => 'voter',
        'getPresentationId' => 'presentation',
    ];

    protected $hasPropertyMappings = [
        'hasVoter' => 'voter',
        'hasPresentation' => 'presentation',
    ];

    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'SummitAttendeeID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendee::class, inversedBy: 'presentation_votes')]
    private $voter;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Presentation::class, inversedBy: 'attendees_votes')]
    private $presentation;

    /**
     * @return SummitAttendee
     */
    public function getVoter(): SummitAttendee
    {
        return $this->voter;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    /**
     * @param SummitAttendee $voter
     * @param Presentation $presentation
     */
    public function __construct(SummitAttendee $voter, Presentation $presentation)
    {
        parent::__construct();
        $this->voter = $voter;
        $this->presentation = $presentation;
    }

    public function clearVoter():void{
        $this->voter = null;
    }

    public function clearPresentation():void{
        $this->presentation = null;
    }

}