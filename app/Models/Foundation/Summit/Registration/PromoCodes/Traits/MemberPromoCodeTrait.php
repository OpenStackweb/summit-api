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
use models\main\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Trait MemberPromoCodeTrait
 * @package models\summit
 */
trait MemberPromoCodeTrait
{
     /**
     * @ORM\Column(name="FirstName", type="string")
     * @var string
     */
    protected $first_name;

    /**
     * @ORM\Column(name="LastName", type="string")
     * @var string
     */
    protected $last_name;

    /**
     * @ORM\Column(name="Email", type="string")
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="OwnerID", referencedColumnName="ID")
     * @var Member
     */
    protected $owner;

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param mixed $first_name
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        if(!empty($this->email)) return $this->email;
        if($this->hasOwner()) return $this->getOwner()->getEmail();
        return null;
    }

    public function getFullName(){
        $fullname = $this->first_name;
        if(!empty($this->last_name)){
            if(!empty($fullname)) $fullname .= ', ';
            $fullname .= $this->last_name;
        }
        if(!empty($fullname)) return $fullname;
        if($this->hasOwner()) return $this->getOwner()->getFullName();
        return null;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try {
            return is_null($this->owner) ? 0: $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasOwner(){
        return $this->getOwnerId() > 0;
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
        return true;
    }
}