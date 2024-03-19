<?php namespace models\summit;
/**
 * Copyright 2024 OpenStack Foundation
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
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitLeadReportSetting")
 * Class SummitLeadReportSetting
 * @package models\summit
 */
class SummitLeadReportSetting extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Sponsor")
     * @ORM\JoinColumn(name="SponsorID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Sponsor
     */
    private $sponsor;

    /**
     * @ORM\Column(name="Columns", type="json")
     * @var array
     */
    protected $columns;

    /**
     * Sponsor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->columns = [];
    }

    /**
     * @return Sponsor
     */
    public function getSponsor(): Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @return int
     */
    public function getSponsorId(): int
    {
        try {
            return is_null($this->sponsor) ? 0: $this->sponsor->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSponsor(): bool
    {
        return $this->getSponsorId() > 0;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function setSponsor(Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

    public function clearSponsor(): void
    {
        $this->sponsor = null;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array  $columns): void
    {
        $this->columns = $columns;
    }
}