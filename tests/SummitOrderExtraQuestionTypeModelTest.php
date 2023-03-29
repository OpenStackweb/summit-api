<?php namespace Tests;
/**
 * Copyright 2022 OpenStack Foundation
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

use models\summit\SummitBadgeFeatureType;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitTicketType;
use ModelSerializers\SerializerRegistry;

/**
 * Class SummitOrderExtraQuestionTypeModelTest
 */
class SummitOrderExtraQuestionTypeModelTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    public function testChangeEventDuration(){
        $soeqt_repository = EntityManager::getRepository(SummitOrderExtraQuestionType::class);
        $sbft_repository = EntityManager::getRepository(SummitBadgeFeatureType::class);
        $stt_repository = EntityManager::getRepository(SummitTicketType::class);

        $soeqt = $soeqt_repository->find(67);
        $sbft = $sbft_repository->find(12);
        $stt1 = $stt_repository->find(2343);
        $stt2 = $stt_repository->find(2344);

        if (!$soeqt instanceof SummitOrderExtraQuestionType) return;

        $soeqt->addAllowedBadgeFeatureType($sbft);

//        $soeqt->addAllowedTicketType($stt1);
//        $soeqt->addAllowedTicketType($stt2);

//        $ticket_types = $soeqt->getAllowedTicketTypes()->toArray();

        $params = [
        ];

        $sm = SerializerRegistry::getInstance()->getSerializer($soeqt)
            ->serialize('allowed_ticket_types,allowed_badge_features_types', [], [], $params);

        self::$em->persist($soeqt);
        self::$em->flush();
    }
}