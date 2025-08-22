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

use App\Repositories\Summit\DoctrineSummitSponsorshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitSponsorship')]
#[ORM\Entity(repositoryClass: DoctrineSummitSponsorshipRepository::class)]
class SummitSponsorship extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTypeId' => 'type',
    ];

    protected $hasPropertyMappings = [
        'hasType' => 'type',
    ];

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Sponsor::class, inversedBy: 'sponsorships')]
    protected $sponsor;

    /**
     * @var SummitSponsorshipAddOn[]
     */
    #[ORM\OneToMany(mappedBy: 'sponsorship', targetEntity: SummitSponsorshipAddOn::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private $add_ons;

    /**
     * @var SummitSponsorshipType
     */
    #[ORM\JoinColumn(name: 'TypeID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: SummitSponsorshipType::class)]
    protected $type;

    public function __construct()
    {
        parent::__construct();
        $this->add_ons = new ArrayCollection();
    }

    public function getSponsor(): Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

    public function clearSponsor(): void
    {
        $this->sponsor = null;
    }

    public function getAddOns(): ArrayCollection|PersistentCollection|array
    {
        return $this->add_ons;
    }

    public function getAddOnById(int $add_on_id): ?SummitSponsorshipAddOn
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $add_on_id));
        $add_on = $this->add_ons->matching($criteria)->first();
        return $add_on === false ? null : $add_on;
    }

    /**
     * @throws ValidationException
     */
    public function addAddOn(SummitSponsorshipAddOn $add_on): void
    {
        if ($this->add_ons->contains($add_on)) return;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($add_on->getType())));
        $criteria->andWhere(Criteria::expr()->eq('name', trim($add_on->getName())));
        if ($this->add_ons->matching($criteria)->count() > 0) {
            throw new ValidationException(sprintf("An add-on with the same name (%s) and type (%s) already exists",
                $add_on->getName(), $add_on->getType()));
        }
        $add_on->setSponsorship($this);
        $this->add_ons->add($add_on);
    }

    public function clearAddOns(): void
    {
        if (is_null($this->add_ons)) return;
        $this->add_ons->clear();
        $this->add_ons = null;
    }
    public function removeAddOn(SummitSponsorshipAddOn $add_on): void
    {
        if (is_null($this->add_ons)) return;
        if (!$this->add_ons->contains($add_on)) return;
        $this->add_ons->removeElement($add_on);
        $add_on->clearSponsorship();
    }


    public function getType(): SummitSponsorshipType
    {
        return $this->type;
    }

    public function setType(SummitSponsorshipType $type): void
    {
        $this->type = $type;
    }
}