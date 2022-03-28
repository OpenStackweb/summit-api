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
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Utils\BaseEntity;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitOwned;
use models\utils\One2ManyPropertyTrait;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="featured_speakers"
 *     )
 * })
 * @ORM\Table(name="Summit_FeaturedSpeakers")
 * Class FeaturedSpeaker
 * @package App\Models\Foundation\Summit\Speakers
 */
class FeaturedSpeaker extends BaseEntity implements IOrderable
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSpeakerId' => 'speaker',
    ];

    protected $hasPropertyMappings = [
        'hasSpeaker' => 'speaker',
    ];

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationSpeaker", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="PresentationSpeakerID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationSpeaker
     */
    private $speaker;

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param int $order
     */
    public function __construct(Summit $summit, PresentationSpeaker $speaker, int $order)
    {
        $this->summit = $summit;
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

}