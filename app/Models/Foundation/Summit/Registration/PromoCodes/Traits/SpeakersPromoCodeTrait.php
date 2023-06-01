<?php namespace models\summit;
/**
 * Copyright 2023 OpenStack Foundation
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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;

/**
 * Trait SpeakersPromoCodeTrait
 * @package models\summit
 */
trait SpeakersPromoCodeTrait
{
    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\OneToMany(targetEntity="AssignedPromoCodeSpeaker", mappedBy="registration_promo_code", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $owners;

    public function __construct()
    {
        parent::__construct();
        $this->owners = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function hasOwners(): bool
    {
        return !$this->owners->isEmpty();
    }

    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return bool
     */
    private function isSpeakerAlreadyAssigned(PresentationSpeaker $speaker): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        $existing_owner = $this->owners->matching($criteria)->first();
        return $existing_owner instanceof AssignedPromoCodeSpeaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return $this
     * @throws ValidationException
     */
    public function assignSpeaker(PresentationSpeaker $speaker)
    {
        if ($this->isSpeakerAlreadyAssigned($speaker)) return $this;
        $owner = new AssignedPromoCodeSpeaker();
        $owner->setSpeaker($speaker);
        $owner->setRegistrationPromoCode($this);
        $this->owners->add($owner);
        return $this;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function unassignSpeaker(PresentationSpeaker $speaker)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        $owner = $this->owners->matching($criteria)->first();
        if ($owner instanceof AssignedPromoCodeSpeaker)
            $this->owners->removeElement($owner);
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return AssignedPromoCodeSpeaker|null
     */
    public function getSpeakerAssignment(PresentationSpeaker $speaker): ?AssignedPromoCodeSpeaker
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('speaker', $speaker));
        $res = $this->owners->matching($criteria)->first();
        return $res == false ? null : $res;
    }
    
    /**
     * @param string $owner_email
     * @param int $usage
     * @throws \Exception
     */
    public function addUsage(string $owner_email, int $usage = 1)
    {
        try {
            $existing_owner = $this->owners->filter(function ($e) use($owner_email){
                return $e->getSpeaker()->getEmail() == $owner_email;
            })->first();

            if (!$existing_owner instanceof AssignedPromoCodeSpeaker)
                throw new ValidationException("can't find an owner with the email {$owner_email} for the promo_code");

            parent::addUsage($owner_email, $usage);
            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
            $existing_owner->setRedeemedAt($utc_now);

        } catch (ValidationException $ex){
            Log::warning($ex);
        }
    }

    /**
     * @param int $to_restore
     * @param string|null $owner_email
     * @throws ValidationException
     */
    public function removeUsage(int $to_restore, string $owner_email = null)
    {
        try {
            if ($owner_email == null)
                throw new ValidationException("owner email is mandatory in order to remove usage for promo code {$this->getId()}");

            $existing_owner = $this->owners->filter(function ($e) use($owner_email){
                return $e->getSpeaker()->getEmail() == $owner_email;
            })->first();

            if (!$existing_owner instanceof AssignedPromoCodeSpeaker)
                throw new ValidationException("can't find an owner with the email {$owner_email} for the promo_code");

            $existing_owner->clearRedeemedAt();

            parent::removeUsage($to_restore);

        } catch (ValidationException $ex){
            Log::warning($ex);
        }
    }

    /**
     * @param bool $email_sent
     * @param string|null $recipient
     * @return void
     */
    public function setEmailSent(bool $email_sent, string $recipient = null)
    {
        Log::debug
        (
            sprintf
            (
                "SpeakersPromoCode::setEmailSent promo_code %s email_sent %b recipient %s",
                $this->getId(),
                $email_sent,
                $recipient
            )
        );

        try{
            $existing_owner = $this->owners->filter(function ($e) use($recipient){
                return $e->getSpeaker()->getEmail() == $recipient;
            })->first();     if (!$existing_owner instanceof AssignedPromoCodeSpeaker)
                throw new ValidationException("can't find an owner with the email {$recipient} for the promo_code");

            parent::setEmailSent($email_sent, $recipient);

            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
            $existing_owner->setSentAt($utc_now);

        } catch (ValidationException $ex){
            Log::warning($ex);
        }
    }
}