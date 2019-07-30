<?php namespace Tests;
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
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Mockery;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitBadgeType;
use models\summit\SummitVenue;
use models\utils\SilverstripeBaseModel;
use LaravelDoctrine\ORM\Facades\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use App\Services\Model\IRegistrationIngestionService;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
/**
 * Class ExternalRegistrationIngestionTest
 * @package Tests
 */
class ExternalRegistrationIngestionTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIngestSummit(){

        $summit = new Summit();
        $summit->setActive(true);
        // set feed type (sched)
        $summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Eventbrite);
        $summit->setExternalRegistrationFeedApiKey(getenv('SUMMIT_REGISTRATION_EXT_API_KEY'));
        $summit->setExternalSummitId(getenv('SUMMIT_REGISTRATION_EXT_SUMMIT_ID'));
        $summit->setTimeZoneId("America/Chicago");
        $summit->setBeginDate(new \DateTime("2019-09-1"));
        $summit->setEndDate(new \DateTime("2019-09-30"));


        $mainVenue = new SummitVenue();
        $mainVenue->setIsMain(true);
        $summit->addLocation($mainVenue);

        $defaultBadge = new SummitBadgeType();
        $defaultBadge->setName("DEFAULT");
        $defaultBadge->setIsDefault(true);
        $summit->addBadgeType($defaultBadge);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->persist($summit);
        $em->flush();

        $service = App::make(IRegistrationIngestionService::class);

        $service->ingestSummit($summit);
    }

    /**
     * @param int $summit_id
     */
    public function testIngestSummitById($summit_id = 29){
        $repo = App::make(ISummitRepository::class);
        $summit =  $repo->getById($summit_id);
        $service = App::make(IRegistrationIngestionService::class);
        $service->ingestSummit($summit);
    }

}