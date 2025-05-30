<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Summit\ScheduleEntity;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitOrderExtraQuestionType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitOrderExtraQuestionTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'order_extra_questions')])]
#[ORM\HasLifecycleCallbacks]
class SummitOrderExtraQuestionType extends ExtraQuestionType
{
    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: '`Usage`', type: 'string')]
    private $usage;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'Printable', type: 'boolean')]
    private $printable;

    /**
     * @var string
     */
    #[ORM\Column(name: '`ExternalId`', type: 'string')]
    private $external_id;

    /**
     * @var SummitTicketType[]
     */
    #[ORM\JoinTable(name: 'SummitOrderExtraQuestionType_SummitTicketType')]
    #[ORM\JoinColumn(name: 'SummitOrderExtraQuestionTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitTicketTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitTicketType::class, inversedBy: 'extra_question_types', fetch: 'EXTRA_LAZY')]
    private $allowed_ticket_types;

    /**
     * @var SummitBadgeFeatureType[]
     */
    #[ORM\JoinTable(name: 'SummitOrderExtraQuestionType_SummitBadgeFeatureType')]
    #[ORM\JoinColumn(name: 'SummitOrderExtraQuestionTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitBadgeFeatureTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitBadgeFeatureType::class, inversedBy: 'extra_question_types', fetch: 'EXTRA_LAZY')]
    private $allowed_badge_features_types;

    /**
     * @return string
     */
    public function getUsage(): string
    {
        return $this->usage;
    }

    /**
     * @param string $usage
     * @throws ValidationException
     */
    public function setUsage(string $usage): void
    {
        if(!in_array($usage, SummitOrderExtraQuestionTypeConstants::ValidQuestionUsages))
            throw new ValidationException(sprintf("%s usage is not valid", $usage));
        $this->usage = $usage;
    }

    /**
     * @return bool
     */
    public function isPrintable(): bool
    {
        return $this->printable;
    }

    /**
     * @param bool $printable
     */
    public function setPrintable(bool $printable): void
    {
        $this->printable = $printable;
    }

    public function __construct()
    {
        parent::__construct();
        $this->printable = false;
        $this->external_id = null;
        $this->allowed_ticket_types = new ArrayCollection();
        $this->allowed_badge_features_types = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string|null $external_id
     */
    public function setExternalId(?string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @return SummitTicketType[]
     */
    public function getAllowedTicketTypes()
    {
        return $this->allowed_ticket_types;
    }

    /**
     * @return int[]
     */
    public function getAllowedTicketTypeIds()
    {
        $ids = [];
        foreach ($this->getAllowedTicketTypes() as $t){
            $ids[] = intval($t->getId());
        }
        return $ids;
    }

    /**
     * @param SummitTicketType $ticket_type
     */
    public function addAllowedTicketType(SummitTicketType $ticket_type): void
    {
        if ($this->allowed_ticket_types->contains($ticket_type)) return;
        $this->allowed_ticket_types->add($ticket_type);
    }

    public function clearAllowedTicketTypes(): void
    {
        $this->allowed_ticket_types->clear();
    }

    /**
     * @return SummitBadgeFeatureType[]
     */
    public function getAllowedBadgeFeatureTypes()
    {
        return $this->allowed_badge_features_types;
    }

    /**
     * @return int[]
     */
    public function getAllowedBadgeFeatureTypeIds()
    {
        $ids = [];
        foreach ($this->getAllowedBadgeFeatureTypes() as $b){
            $ids[] = intval($b->getId());
        }
        return $ids;
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function addAllowedBadgeFeatureType(SummitBadgeFeatureType $feature_type): void
    {
        if ($this->allowed_badge_features_types->contains($feature_type)) return;
        $this->allowed_badge_features_types->add($feature_type);
    }

    public function clearAllowedBadgeFeatureTypes(): void
    {
        $this->allowed_badge_features_types->clear();
    }

    use ScheduleEntity;
}