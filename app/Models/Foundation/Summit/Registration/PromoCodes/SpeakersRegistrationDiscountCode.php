<?php namespace models\summit;
/**
 * Copyright 2023 OpenStack Foundation
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSpeakersRegistrationDiscountCodeRepository")
 * @ORM\Table(name="SpeakersRegistrationDiscountCode")
 * Class SpeakersRegistrationDiscountCode
 * This entity has the purpose of allowing to associate several speakers to a single discount code
 * @package models\summit
 */
class SpeakersRegistrationDiscountCode
    extends SummitRegistrationDiscountCode
{
    use SpeakersPromoCodeTrait;

    const ClassName = 'SpeakersRegistrationDiscountCode';

    /**
     * @ORM\OneToMany(targetEntity="AssignedPromoCodeSpeaker", mappedBy="registration_discount_code", cascade={"persist"}, orphanRemoval=true)
     * @var AssignedPromoCodeSpeaker[]
     */
    private $owners;

    public static $metadata = [
        'class_name' => self::ClassName
    ];

    /**
     * @return array
     */
    public static function getMetadata()
    {
        return array_merge(SummitRegistrationDiscountCode::getMetadata(), self::$metadata);
    }

    public function getClassName(): string
    {
        return self::ClassName;
    }

    public function __construct()
    {
        parent::__construct();
        $this->owners = new ArrayCollection();
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function assignSpeaker(PresentationSpeaker $speaker)
    {
        if ($this->isSpeakerAlreadyAssigned($speaker)) return;
        $owner = new AssignedPromoCodeSpeaker();
        $owner->setSpeaker($speaker);
        $owner->setRegistrationDiscountCode($this);
        $this->owners->add($owner);
    }
}