<?php namespace App\Models\Foundation\Marketplace;

/**
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

use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="CustomerCaseStudy",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="owner_name",
 *             columns={"Name", "OwnerID"}
 *         )
 *     }
 * )
 * Class CustomerCaseStudy
 * @package App\Models\Foundation\Marketplace
 */
class CustomerCaseStudy extends SilverstripeBaseModel
{
    const ClassName = 'CustomerCaseStudy';

    /**
     * @ORM\Column(name="Name", type="string", length=250)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Uri", type="string", length=250, nullable=true)
     * @var string
     */
    private $uri;

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="CompanyService", inversedBy="case_studies", fetch="LAZY")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID", nullable=false, onDelete="CASCADE")
     * @var CompanyService
     */
    private $company_service;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri !== null ? trim($uri) : null;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return CompanyService
     */
    public function getOwner(): CompanyService
    {
        return $this->company_service;
    }

    public function setOwner(CompanyService $new_owner): self
    {
        $this->company_service = $new_owner;
        return $this;
    }
}
