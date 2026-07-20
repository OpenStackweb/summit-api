<?php namespace models\summit;
/*
 * Copyright 2026 OpenStack Foundation
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

use App\Repositories\Summit\DoctrineSummitSponsorshipAddOnTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitSponsorshipAddOnType')]
#[ORM\Entity(repositoryClass: DoctrineSummitSponsorshipAddOnTypeRepository::class)]
class SummitSponsorshipAddOnType extends SilverstripeBaseModel
{
    public const Booth_Type = 'Booth';
    public const Meeting_Room_Type = 'Meeting_Room';
    public const Schedule_Spot_Type = 'Schedule_Spot';
    public const Signage_Spot_Type = 'Signage_Spot';

    public const SystemDefined_Types = [
        self::Booth_Type,
        self::Meeting_Room_Type,
        self::Schedule_Spot_Type,
        self::Signage_Spot_Type,
    ];

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isSystemDefined(): bool
    {
        return in_array($this->name, self::SystemDefined_Types);
    }
}
