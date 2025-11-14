<?php namespace models\summit;
/*
 * Copyright 2025 OpenStack Foundation
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

use App\Repositories\Summit\DoctrineSummitSponsorshipAddOnRepository;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitSponsorshipAddOn')]
#[ORM\Entity(repositoryClass: DoctrineSummitSponsorshipAddOnRepository::class)]
class SummitSponsorshipAddOn extends SilverstripeBaseModel
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsorshipId' => 'sponsorship',
    ];

    protected $hasPropertyMappings = [
        'hasSponsorship' => 'sponsorship',
    ];

    const Booth_Type = 'Booth';
    const MeetingRoom_Type = 'Meeting_Room';
    const ScheduleSpot_Type = 'Schedule_Spot';
    const SignageSpot_Type = 'Signage_Spot';

    const ValidTypes = [
        self::Booth_Type,
        self::MeetingRoom_Type,
        self::ScheduleSpot_Type,
        self::SignageSpot_Type,
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'Type', type: 'string')]
    private $type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

     /**
     * @var SummitSponsorship
     */
    #[ORM\ManyToOne(targetEntity: SummitSponsorship::class, fetch: 'EXTRA_LAZY', inversedBy: 'add_ons')]
    #[ORM\JoinColumn(name: 'SponsorshipID', referencedColumnName: 'ID')]
    protected $sponsorship;

    public static function getMetadata(): array
    {
        return self::ValidTypes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @throws ValidationException
     */
    public function setType(string $type): void
    {
        if(!in_array($type, self::ValidTypes))
            throw new ValidationException(sprintf("%s is not a valid type.", $type));
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSponsorship(): SummitSponsorship
    {
        return $this->sponsorship;
    }

    public function setSponsorship(SummitSponsorship $sponsorship): void
    {
        $this->sponsorship = $sponsorship;
    }

    public function clearSponsorship(): void
    {
        $this->sponsorship = null;
    }
}
