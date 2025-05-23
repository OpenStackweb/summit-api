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

use models\exceptions\ValidationException;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Trait SponsorPromoCodeTrait
 * @package models\summit
 */
trait SponsorPromoCodeTrait
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'ContactEmail', type: 'string')]
    protected $contact_email;

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Sponsor::class)]
    protected $sponsor;

    /**
     * @return string
     */
    public function getType():string
    {
        return 'SPONSOR';
    }

    /**
     * @return int
     */
    public function getSponsorId():int{
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
    public function hasSponsor():bool{
        return $this->getSponsorId() > 0;
    }

    public function getContactEmail(): ?string
    {
        return $this->contact_email;
    }

    public function setContactEmail(string $contact_email): void
    {
        $this->contact_email = $contact_email;
    }

    /**
     * @return Sponsor
     */
    public function getSponsor():Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function setSponsor(Sponsor $sponsor)
    {
        $this->sponsor = $sponsor;
    }

    /**
     * @param string $email
     * @param null|string $company
     * @return bool
     * @throw ValidationException
     */
    public function checkSubject(string $email, ?string $company):bool{
        return true;
    }
}