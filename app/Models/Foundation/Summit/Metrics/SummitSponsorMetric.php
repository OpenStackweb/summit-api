<?php namespace models\summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\main\Member;
/**
 * Class SummitSponsorMetric
 * @package models\summit
 */
#[ORM\Table(name: 'SummitSponsorMetric')]
#[ORM\Entity]
class SummitSponsorMetric extends SummitMetric
{
    /**
     * @var Sponsor|null
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Sponsor::class)]
    protected $sponsor;

    /**
     * @return int
     */
    public function getSponsorId(){
        try {
            return is_null($this->sponsor) ? 0 : $this->sponsor->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSponsor():bool{
        return $this->getSponsorId() > 0;
    }

    /**
     * @return Sponsor|null
     */
    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @param Sponsor|null $sponsor
     */
    public function setSponsor(?Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

}