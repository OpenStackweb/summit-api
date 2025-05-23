<?php namespace App\Models\Foundation\Summit\Speakers;
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
use App\Models\Foundation\Summit\ScheduleEntity;
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Utils\BaseEntity;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use models\utils\One2ManyPropertyTrait;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'Presentation_Speakers')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitPresentationSpeakerAssignmentRepository::class)]
#[ORM\HasLifecycleCallbacks] // Class PresentationSpeakerAssignment
class PresentationSpeakerAssignment extends BaseEntity implements IOrderable
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSpeakerId' => 'speaker',
        'getPresentationId' => 'presentation',
    ];

    protected $hasPropertyMappings = [
        'hasSpeaker' => 'speaker',
        'hasPresentation' => 'presentation',
    ];

    /**
     * @var PresentationSpeaker
     */
    #[ORM\JoinColumn(name: 'PresentationSpeakerID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\PresentationSpeaker::class, fetch: 'EXTRA_LAZY', inversedBy: 'presentations')]
    private $speaker;

    /**
     * @var Presentation
     */
    #[ORM\JoinColumn(name: 'PresentationID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Presentation::class, fetch: 'EXTRA_LAZY', inversedBy: 'speakers')]
    private $presentation;

    /**
     * @var int
     */
    #[ORM\Column(name: 'CustomOrder', type: 'integer')]
    private $order;

    /**
     * @param Presentation $presentation
     * @param PresentationSpeaker $speaker
     * @param int $order
     */
    public function __construct(Presentation $presentation, PresentationSpeaker $speaker, int $order)
    {
        $this->presentation = $presentation;
        $this->speaker = $speaker;
        $this->order = $order;
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
     * @return PresentationSpeaker
     */
    public function getSpeaker(): ?PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker(PresentationSpeaker $speaker): void
    {
        $this->speaker = $speaker;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): ?Presentation
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

    /**
     * @return int
     */
    public function getSummitId(): int
    {
        return $this->presentation->getSummitId();
    }

    use ScheduleEntity;
}