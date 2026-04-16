<?php namespace App\Models\Foundation\Summit;
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

use Doctrine\ORM\Mapping as ORM;
use models\summit\Sponsor;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SponsorStatistics')]
#[ORM\Entity]
class SponsorStatistics extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    /**
     * @var Sponsor|null
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\OneToOne(targetEntity: Sponsor::class, inversedBy: 'sponsorservices_statistics', fetch: 'EXTRA_LAZY')]
    private $sponsor;

    /**
     * @var int
     */
    #[ORM\Column(name: 'FormsQty', type: 'integer')]
    private $formsQty;

    /**
     * @var int
     */
    #[ORM\Column(name: 'PurchasesQty', type: 'integer')]
    private $purchasesQty;

    /**
     * @var int
     */
    #[ORM\Column(name: 'PagesQty', type: 'integer')]
    private $pagesQty;

    /**
     * @var int
     */
    #[ORM\Column(name: 'DocumentsQty', type: 'integer')]
    private $documentsQty;

    public function __construct()
    {
        parent::__construct();
        $this->formsQty = 0;
        $this->purchasesQty = 0;
        $this->pagesQty = 0;
        $this->documentsQty = 0;
    }

    public function getSponsor(): ?Sponsor
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

    public function getFormsQty(): int
    {
        return $this->formsQty;
    }

    public function setFormsQty(int $formsQty): void
    {
        $this->formsQty = $formsQty;
    }

    public function getPurchasesQty(): int
    {
        return $this->purchasesQty;
    }

    public function setPurchasesQty(int $purchasesQty): void
    {
        $this->purchasesQty = $purchasesQty;
    }

    public function getPagesQty(): int
    {
        return $this->pagesQty;
    }

    public function setPagesQty(int $pagesQty): void
    {
        $this->pagesQty = $pagesQty;
    }

    public function getDocumentsQty(): int
    {
        return $this->documentsQty;
    }

    public function setDocumentsQty(int $documentsQty): void
    {
        $this->documentsQty = $documentsQty;
    }
}
