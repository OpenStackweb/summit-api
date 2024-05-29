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

use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeed;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\IMemberService;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use App\Services\Model\ISummitTicketTypeService;
use models\summit\SummitTicketType;
use Illuminate\Support\Facades\App;
use Mockery;
use models\summit\Summit;
use models\summit\SummitBadgeType;
use models\summit\SummitVenue;
use models\utils\SilverstripeBaseModel;
use LaravelDoctrine\ORM\Facades\Registry;
use App\Services\Model\IRegistrationIngestionService;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;

/**
 * Class ExternalRegistrationIngestionTest
 * @package Tests
 */
class ExternalRegistrationIngestionTest extends \Tests\BrowserKitTestCase
{

    protected function prePrepareForTest():void{
        $member_service = Mockery::mock(IMemberService::class);
        $member_service->shouldReceive('checkExternalUser')->andReturn([
                'id' => '123456',
                'first_name' => 'test',
                'last_name' => 'test',
                'email' => 'test@test.com',
                'user_active' => true,
                'email_verified' => true,
            ]
        );

        $this->app->singleton(IMemberService::class, function() use ($member_service){
            return $member_service;
        });
    }

    public function tearDown():void
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
        $ticketTypeService = App::make(ISummitTicketTypeService::class);
        $extraOrderQuestionService = App::make(ISummitOrderExtraQuestionTypeService::class);

        $extraOrderQuestionService->seedSummitOrderExtraQuestionTypesFromEventBrite($summit);

        $this->assertTrue($summit->getOrderExtraQuestions()->count() > 0);

        $ticketTypeService->seedSummitTicketTypesFromEventBrite($summit);

        $this->assertTrue($summit->getTicketTypes()->count() > 0);

        $service->ingestSummit($summit);

        $this->assertTrue($summit->getAttendeesCount() > 0);
    }

    public function testIngestSummitSamsungAPI(){

        $summit = new Summit();
        $summit->setActive(true);
        // set feed type (sched)
        $summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Samsung);
        $summit->setExternalRegistrationFeedApiKey(getenv('SUMMIT_REGISTRATION_EXT_API_KEY'));
        $summit->setExternalSummitId(getenv('SUMMIT_REGISTRATION_EXT_SUMMIT_ID'));
        $summit->setTimeZoneId("America/Los_Angeles");
        $summit->setBeginDate(new \DateTime("2023-10-6"));
        $summit->setEndDate(new \DateTime("2023-10-7"));

        // registration metdata

        $summit->addRegistrationFeedMetadata("forum", "Tech day 2023");
        $summit->addRegistrationFeedMetadata("gbm", "LSI");
        $summit->addRegistrationFeedMetadata("region", "LS");
        $summit->addRegistrationFeedMetadata("year", "2023");

        $mainVenue = new SummitVenue();
        $mainVenue->setIsMain(true);
        $summit->addLocation($mainVenue);


        $defaultBadge = new SummitBadgeType();
        $defaultBadge->setName("DEFAULT");
        $defaultBadge->setIsDefault(true);
        $summit->addBadgeType($defaultBadge);

        $defaultTicketType = new SummitTicketType();
        $defaultTicketType->setName("DEFAULT");
        $defaultTicketType->setDescription("DEFAULT");
        $defaultTicketType->setAudience(SummitTicketType::Audience_All);
        $defaultTicketType->setCost(0.0);
        $defaultTicketType->setBadgeType($defaultBadge);
        $summit->addTicketType($defaultTicketType);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->persist($summit);
        $em->flush();

        $service = App::make(IRegistrationIngestionService::class);
        $this->assertTrue($summit->getTicketTypes()->count() > 0);

        $service->ingestSummit($summit);

        $this->assertTrue($summit->getAttendeesCount() > 0);
    }

    public function testAttendeeUserIdChange(){

        $summit = new Summit();
        $summit->setActive(true);
        // set feed type (sched)
        $summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Samsung);
        $summit->setExternalRegistrationFeedApiKey('SUMMIT_REGISTRATION_EXT_API_KEY');
        $summit->setExternalSummitId("EXTERNAL_ID");
        $summit->setTimeZoneId("America/Los_Angeles");
        $summit->setBeginDate(new \DateTime("2023-10-6"));
        $summit->setEndDate(new \DateTime("2023-10-7"));
        $summit->setRawSlug("test-summit");

        // registration metadata

        $summit->addRegistrationFeedMetadata("forum", "Tech day 2023");
        $summit->addRegistrationFeedMetadata("gbm", "LSI");
        $summit->addRegistrationFeedMetadata("region", "LS");
        $summit->addRegistrationFeedMetadata("year", "2023");

        $mainVenue = new SummitVenue();
        $mainVenue->setIsMain(true);
        $summit->addLocation($mainVenue);

        $defaultBadge = new SummitBadgeType();
        $defaultBadge->setName("DEFAULT");
        $defaultBadge->setIsDefault(true);
        $summit->addBadgeType($defaultBadge);

        $defaultTicketType = new SummitTicketType();
        $defaultTicketType->setName("DEFAULT");
        $defaultTicketType->setDescription("DEFAULT");
        $defaultTicketType->setAudience(SummitTicketType::Audience_All);
        $defaultTicketType->setCost(0.0);
        $defaultTicketType->setBadgeType($defaultBadge);
        $summit->addTicketType($defaultTicketType);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->persist($summit);
        $em->flush();

        $service = App::make(IRegistrationIngestionService::class);
        $this->assertTrue($service instanceof IRegistrationIngestionService);
        $this->assertTrue($summit->getTicketTypes()->count() > 0);

        $feed = Mockery::mock(IExternalRegistrationFeed::class);
        $feed->shouldReceive('shouldCreateExtraQuestions')->andReturn(false);

        $id = '123456';
        $external_attendee_data = [
            'id' => $id,
            'profile' => [
                'id' => $id,
                'email' => 'test@test.com',
                'first_name' => 'test',
                'last_name' => 'test',
                'company' => 'test',
            ],
            'ticket_class' => ['id' => $defaultTicketType->getId()],
            'order' => [
                'id' => $id,
                'email' => 'test@test.com',
                'first_name' => 'test',
                'last_name' => 'test',
                'company' => 'test',
            ],
        ];

        $attendee = $service->ingestExternalAttendee($summit->getId(), 0, $external_attendee_data, $feed);

        $this->assertTrue(!is_null($attendee));
        $this->assertTrue($attendee->getTickets()->count() == 1);

        // change id
        $id = 'ABCDFGH';
        $external_attendee_data = [
            'id' => $id,
            'profile' => [
                'id' => $id,
                'email' => 'test@test.com',
                'first_name' => 'test',
                'last_name' => 'test',
                'company' => 'test',
            ],
            'ticket_class' => ['id' => $defaultTicketType->getId()],
            'order' => [
                'id' => $id,
                'email' => 'test@test.com',
                'first_name' => 'test',
                'last_name' => 'test',
                'company' => 'test',
            ],
        ];

        $update_attendee = $service->ingestExternalAttendee($summit->getId(), 0, $external_attendee_data, $feed);
        $this->assertTrue($update_attendee->getTickets()->count() == 1);
    }
}