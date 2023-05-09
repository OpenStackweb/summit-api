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

use Doctrine\Common\Collections\Criteria;
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
        return count($this->owners) > 0;
    }

    /**
     * @return AssignedPromoCodeSpeaker[]
     */
    public function getOwners(): array
    {
        return $this->owners->toArray();
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
     * @param int $usage
     * @param string $owner_email
     * @throws \Exception
     */
    public function addUsage(int $usage, string $owner_email)
    {
        try {
            $existing_owner = $this->owners->filter(function ($e) use($owner_email){
                $member = $e->getSpeaker()->getMember();
                if (is_null($member)) return false;
                return $member->getEmail() == $owner_email;
            })->first();

            if (!$existing_owner instanceof AssignedPromoCodeSpeaker)
                throw new ValidationException("can't find an owner with the email {$owner_email} for the promo_code");

            parent::addUsage($usage, $owner_email);
            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
            $existing_owner->setRedeemedAt($utc_now);
        }
        catch (ValidationException $ex){
            Log::warning($ex);
        }
    }
}