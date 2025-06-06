<?php namespace App\Models\Foundation\Summit\Defaults;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitEventType;

/**
 * Class DefaultPresentationType
 * @package App\Models\Foundation\Summit\Defaults
 */
#[ORM\Table(name: 'DefaultPresentationType')]
#[ORM\Entity]
class DefaultPresentationType extends DefaultSummitEventType
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'MaxSpeakers', type: 'integer')]
    protected $max_speakers;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MinSpeakers', type: 'integer')]
    protected $min_speakers;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MaxModerators', type: 'integer')]
    protected $max_moderators;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MinModerators', type: 'integer')]
    protected $min_moderators;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'UseSpeakers', type: 'boolean')]
    protected $use_speakers;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'AreSpeakersMandatory', type: 'boolean')]
    protected $are_speakers_mandatory;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'UseModerator', type: 'boolean')]
    protected $use_moderator;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsModeratorMandatory', type: 'boolean')]
    protected $is_moderator_mandatory;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'ShouldBeAvailableOnCFP', type: 'boolean')]
    protected $should_be_available_on_cfp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ModeratorLabel', type: 'string')]
    protected $moderator_label;

    /**
     * @return int
     */
    public function getMaxSpeakers()
    {
        return $this->max_speakers;
    }

    /**
     * @param int $max_speakers
     */
    public function setMaxSpeakers($max_speakers)
    {
        $this->max_speakers = $max_speakers;
    }

    /**
     * @return int
     */
    public function getMinSpeakers()
    {
        return $this->min_speakers;
    }

    /**
     * @param int $min_speakers
     */
    public function setMinSpeakers($min_speakers)
    {
        $this->min_speakers = $min_speakers;
    }

    /**
     * @return int
     */
    public function getMaxModerators()
    {
        return $this->max_moderators;
    }

    /**
     * @param int $max_moderators
     */
    public function setMaxModerators($max_moderators)
    {
        $this->max_moderators = $max_moderators;
    }

    /**
     * @return int
     */
    public function getMinModerators()
    {
        return $this->min_moderators;
    }

    /**
     * @param int $min_moderators
     */
    public function setMinModerators($min_moderators)
    {
        $this->min_moderators = $min_moderators;
    }

    /**
     * @return bool
     */
    public function isUseSpeakers()
    {
        return $this->use_speakers;
    }

    /**
     * @param bool $use_speakers
     */
    public function setUseSpeakers($use_speakers)
    {
        $this->use_speakers = $use_speakers;
    }

    /**
     * @return bool
     */
    public function isAreSpeakersMandatory()
    {
        return $this->are_speakers_mandatory;
    }

    /**
     * @param bool $are_speakers_mandatory
     */
    public function setAreSpeakersMandatory($are_speakers_mandatory)
    {
        $this->are_speakers_mandatory = $are_speakers_mandatory;
    }

    /**
     * @return bool
     */
    public function isUseModerator()
    {
        return $this->use_moderator;
    }

    /**
     * @param bool $use_moderator
     */
    public function setUseModerator($use_moderator)
    {
        $this->use_moderator = $use_moderator;
    }

    /**
     * @return bool
     */
    public function isModeratorMandatory()
    {
        return $this->is_moderator_mandatory;
    }

    /**
     * @param bool $is_moderator_mandatory
     */
    public function setIsModeratorMandatory($is_moderator_mandatory)
    {
        $this->is_moderator_mandatory = $is_moderator_mandatory;
    }

    /**
     * @return bool
     */
    public function isShouldBeAvailableOnCfp()
    {
        return $this->should_be_available_on_cfp;
    }

    /**
     * @param bool $should_be_available_on_cfp
     */
    public function setShouldBeAvailableOnCfp($should_be_available_on_cfp)
    {
        $this->should_be_available_on_cfp = $should_be_available_on_cfp;
    }

    /**
     * @return string
     */
    public function getModeratorLabel()
    {
        return $this->moderator_label;
    }

    /**
     * @param string $moderator_label
     */
    public function setModeratorLabel($moderator_label)
    {
        $this->moderator_label = $moderator_label;
    }

    protected function newType(){
        return new PresentationType();
    }

    /**
     * @param Summit $summit
     * @return SummitEventType
     */
    public function buildType(Summit $summit){
        $new_type = parent::buildType($summit);
        $new_type->setMaxSpeakers($this->max_speakers);
        $new_type->setMinSpeakers($this->min_speakers);
        $new_type->setMaxModerators($this->max_moderators);
        $new_type->setMinModerators($this->min_moderators);
        $new_type->setUseSpeakers($this->use_speakers);
        $new_type->setAreSpeakersMandatory($this->are_speakers_mandatory);
        $new_type->setUseModerator($this->use_moderator);
        $new_type->setIsModeratorMandatory($this->is_moderator_mandatory);
        $new_type->setShouldBeAvailableOnCfp($this->should_be_available_on_cfp);
        if(!empty($this->moderator_label))
            $new_type->setModeratorLabel($this->moderator_label);
        return $new_type;
    }

    public function __construct()
    {
        parent::__construct();
        $this->use_speakers = false;
        $this->are_speakers_mandatory = false;
        $this->use_moderator = false;
        $this->is_moderator_mandatory = false;
        $this->should_be_available_on_cfp = false;
        $this->moderator_label = '';
    }
}