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
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * Class SummitSchedulePreFilterElementConfig
 * @ORM\Table(name="SummitSchedulePreFilterElementConfig")
 * @package models\summit
 */
class SummitSchedulePreFilterElementConfig extends SilverstripeBaseModel
{
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
     * @ORM\Column(name="Values", type="string")
     * @var string
     */
    private $values;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitScheduleConfig", fetch="EXTRA_LAZY", inversedBy="pre_filters")
     * @ORM\JoinColumn(name="SummitScheduleConfigID", referencedColumnName="ID")
     * @var SummitScheduleConfig
     */
    protected $config;

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

    /**
     * @return array
     */
    public function getValues(): array
    {
        return  explode(",", $this->values);
    }

    /**
     * @param array $values
     */
    public function setValues(array $values): void
    {
        $this->values = implode(',', $values);
    }

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
        if(!in_array($type, SummitScheduleFilterElementConfig::AllowedTypes))
            throw new ValidationException(sprintf("Type %s is not valid.", $type));
        $this->type = $type;
    }
}