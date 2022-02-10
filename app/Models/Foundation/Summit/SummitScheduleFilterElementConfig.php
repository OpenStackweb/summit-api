<?php namespace models\summit;
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
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * Class SummitScheduleFilterElementConfig
 * @ORM\Table(name="SummitScheduleFilterElementConfig")
 * @package models\summit
 */
class SummitScheduleFilterElementConfig extends SilverstripeBaseModel
{
    const Type_Date = 'DATE';
    const Type_Track = 'TRACK';
    const Type_Tags  = 'TAGS';
    const Type_TrackGroups = 'TRACK_GROUPS';
    const Type_Company = 'COMPANY';
    const Type_Level = 'LEVEL';
    const Type_Speakers = 'SPEAKERS';
    const Type_Venues = 'VENUES';
    const Type_EventTypes = 'EVENT_TYPES';
    const Type_Title = 'TITLE';
    const Type_CustomOrder = 'CUSTOM_ORDER';
    const Type_Abstract = 'ABSTRACT';

    const AllowedTypes =
    [
        self::Type_Date,
        self::Type_Track,
        self::Type_Tags,
        self::Type_TrackGroups,
        self::Type_Company,
        self::Type_Level,
        self::Type_Speakers,
        self::Type_Venues,
        self::Type_EventTypes,
        self::Type_Title,
        self::Type_CustomOrder,
        self::Type_Abstract,
    ];

    const DefaultLabelsByType = [
        self::Type_Date => 'Date',
        self::Type_Track => 'Categories',
        self::Type_Tags => 'Tags',
        self::Type_TrackGroups => 'Categories Groups',
        self::Type_Company => 'Company',
        self::Type_Level => 'Level',
        self::Type_Speakers => 'Speakers',
        self::Type_Venues => 'Venues',
        self::Type_EventTypes => 'Activity Types',
        self::Type_Title => 'Title',
        self::Type_CustomOrder => 'Custom Order',
        self::Type_Abstract => 'Abstract',
    ];

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getConfigId' => 'config',
    ];

    protected $hasPropertyMappings = [
        'hasConfig' => 'config',
    ];

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @ORM\Column(name="IsEnabled", type="boolean")
     * @var bool
     */
    private $is_enabled;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitScheduleConfig", fetch="EXTRA_LAZY", inversedBy="filters")
     * @ORM\JoinColumn(name="SummitScheduleConfigID", referencedColumnName="ID")
     * @var SummitScheduleConfig
     */
    protected $config;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function setType(string $type): void
    {
        if(!in_array($type, self::AllowedTypes))
            throw new ValidationException(sprintf("Type %s is not valid.", $type));
        $this->type = $type;
        $this->label = self::DefaultLabelsByType[$type];
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
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return SummitScheduleConfig
     */
    public function getConfig(): SummitScheduleConfig
    {
        return $this->config;
    }

    /**
     * @param SummitScheduleConfig $config
     */
    public function setConfig(SummitScheduleConfig $config): void
    {
        $this->config = $config;
    }

    public function clearConfig(){
        $this->config = null;
    }
}