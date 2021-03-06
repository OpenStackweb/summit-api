<?php namespace App\Repositories\Summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\summit\ISpeakerSummitRegistrationPromoCodeRepository;
use models\summit\PresentationSpeaker;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class DoctrineSpeakerSummitRegistrationPromoCodeRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSpeakerSummitRegistrationPromoCodeRepository
    extends DoctrineSummitRegistrationPromoCodeRepository
    implements ISpeakerSummitRegistrationPromoCodeRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SpeakerSummitRegistrationPromoCode::class;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param Summit $summit
     * @return SpeakerSummitRegistrationPromoCode
     */
    public function getBySpeakerAndSummit(PresentationSpeaker $speaker, Summit $summit)
    {
        if($speaker->getId() == 0) return null;
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from(SpeakerSummitRegistrationPromoCode::class, "c")
            ->where("c.speaker = :speaker")
            ->andWhere("c.summit = :summit")
            ->setParameter("speaker", $speaker)
            ->setParameter("summit", $summit)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $code
     * @param Summit $summit
     * @return bool
     */
    public function isAssignedCode($code, Summit $summit)
    {
       return $this->getAssignedCode($code, $summit) != null;
    }

    /**
     * @param string $code
     * @param Summit $summit
     * @return SpeakerSummitRegistrationPromoCode
     */
    public function getAssignedCode($code, Summit $summit)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from(SpeakerSummitRegistrationPromoCode::class, "c")
            ->where("c.speaker is not null")
            ->andWhere("c.summit = :summit")
            ->andWhere("c.code = :code")
            ->setParameter("summit", $summit)
            ->setParameter("code", trim($code))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $code
     * @param Summit $summit
     * @return SpeakerSummitRegistrationPromoCode
     */
    public function getNotAssignedCode($code, Summit $summit)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from(SpeakerSummitRegistrationPromoCode::class, "c")
            ->where("c.speaker is null")
            ->andWhere("c.summit = :summit")
            ->andWhere("c.code = :code")
            ->setParameter("summit", $summit)
            ->setParameter("code", trim($code))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $type
     * @return SummitRegistrationPromoCode
     */
    public function getNextAvailableByType(Summit $summit, string $type):?SummitRegistrationPromoCode
    {
        $res =  $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from(SpeakerSummitRegistrationPromoCode::class, "e")
            ->where("e.speaker is null")
            ->andWhere("e.summit = :summit")
            ->andWhere("e.type = :type")
            ->setParameter("summit", $summit)
            ->setParameter("type", trim($type))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if(is_null($res)){
             $res =  $this->getEntityManager()
                 ->createQueryBuilder()
                 ->select("e")
                 ->from(SpeakerSummitRegistrationDiscountCode::class, "e")
                 ->where("e.speaker is null")
                 ->andWhere("e.summit = :summit")
                 ->andWhere("e.type = :type")
                 ->setParameter("summit", $summit)
                 ->setParameter("type", trim($type))
                 ->setMaxResults(1)
                 ->getQuery()
                 ->getOneOrNullResult();

        }
        return $res;
    }
}