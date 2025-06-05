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

use App\Models\Foundation\Summit\Registration\Traits\TaxTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @package models\summitSummitTicketType_Taxes
 */
#[ORM\Table(name: 'SummitTaxType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitTaxTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'tax_types')])]
class SummitTaxType extends SilverstripeBaseModel
{
    use TaxTrait;

    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TaxID', type: 'string')]
    private $tax_id;

    /**
     * @var double
     */
    #[ORM\Column(name: 'Rate', type: 'float')]
    private $rate;

    /**
     * @var SummitTicketType[]
     */
    #[ORM\JoinTable(name: 'SummitTicketType_Taxes')]
    #[ORM\JoinColumn(name: 'SummitTaxTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitTicketTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitTicketType::class, inversedBy: 'applied_taxes')]
    private $ticket_types;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getTaxId(): ?string
    {
        return $this->tax_id;
    }

    /**
     * @param string $tax_id
     */
    public function setTaxId(string $tax_id): void
    {
        $this->tax_id = $tax_id;
    }

    public function __construct()
    {
        parent::__construct();
        $this->ticket_types = new ArrayCollection();
        $this->tax_id = "";
        $this->rate = 0.0;
    }

    /**
     * @param SummitTicketType $ticketType
     */
    public function addTicketType(SummitTicketType $ticketType){
        if($this->ticket_types->contains($ticketType)) return;
        $this->ticket_types->add($ticketType);
    }

    /**
     * @param SummitTicketType $ticketType
     */
    public function removeTicketType(SummitTicketType $ticketType){
        if(!$this->ticket_types->contains($ticketType)) return;
        $this->ticket_types->removeElement($ticketType);
    }

    /**
     * @return SummitTicketType[]
     */
    public function getTicketTypes()
    {
        return $this->ticket_types;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return bool
     */
    public function mustApplyTo(SummitTicketType $ticket_type):bool{
        if($this->ticket_types->count() == 0) return true;
        return $this->ticket_types->contains($ticket_type);
    }

}