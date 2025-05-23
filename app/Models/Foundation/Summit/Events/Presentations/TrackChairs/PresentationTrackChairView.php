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
use models\main\Member;
use models\summit\Presentation;
use models\utils\SilverstripeBaseModel;
use models\utils\One2ManyPropertyTrait;
/**
 * @package models\summit;
 */
#[ORM\Table(name: 'PresentationTrackChairView')]
#[ORM\Entity]
class PresentationTrackChairView extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getViewerId' => 'viewer',
        'getPresentationId' => 'presentation',
    ];

    protected $hasPropertyMappings = [
        'hasViewer' => 'viewer',
        'hasPresentation' => 'presentation',
    ];

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'TrackChairID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    private $viewer;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Presentation::class, inversedBy: 'track_chair_views')]
    private $presentation;

    /**
     * @param Member $viewer
     * @param Presentation $presentation
     * @return PresentationTrackChairView
     */
    public static function build(Member $viewer, Presentation $presentation){
        $view =  new PresentationTrackChairView();
        $view->viewer = $viewer;
        $view->presentation = $presentation;
        return $view;
    }

    /**
     * @return Member
     */
    public function getViewer(): Member
    {
        return $this->viewer;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

}