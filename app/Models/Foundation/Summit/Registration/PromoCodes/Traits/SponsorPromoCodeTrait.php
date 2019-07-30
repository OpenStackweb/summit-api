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
use models\main\Company;
use models\main\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Trait SponsorPromoCodeTrait
 * @package models\summit
 */
trait SponsorPromoCodeTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="models\main\Company")
     * @ORM\JoinColumn(name="SponsorID", referencedColumnName="ID")
     * @var Company
     */
    protected $sponsor;

    /**
     * @return string
     */
    public function getType()
    {
        return 'SPONSOR';
    }

    /**
     * @return int
     */
    public function getSponsorId(){
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
    public function hasSponsor(){
        return $this->getSponsorId() > 0;
    }

    /**
     * @return Company
     */
    public function getSponsor()
    {
        return $this->sponsor;
    }

    /**
     * @param Company $sponsor
     */
    public function setSponsor($sponsor)
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

        if($this->hasOwner() && $this->getOwnerEmail() != $email){
            throw new ValidationException(sprintf('The Promo Code “%s” is not valid for the %s. Promo Code restrictions are associated with the purchaser email not the attendee.', $this->getCode(), $email));
        }

        if(!empty($company) &&$this->hasSponsor() && $this->getSponsor()->getName() != $company){
            throw new ValidationException(sprintf("The Promo Code %s is not available for Company %s", $this->getCode(), $company));
        }
        return true;
    }
}