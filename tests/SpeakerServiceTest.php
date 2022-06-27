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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Models\Foundation\Main\IGroup;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SpeakerAnnouncementSummitEmail;
use services\model\ISpeakerService;

/**
 * Class SpeakerServiceTest
 */
final class SpeakerServiceTest extends TestCase
{
    //use InsertSummitTestData;

    //use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        //self::insertMemberTestData(IGroup::TrackChairs);
        //self::insertTestData();
    }

    protected function tearDown(): void
    {
        //self::clearTestData();
        //self::clearMemberTestData();
        parent::tearDown();
    }

    public function testSendAcceptedAlternateEmailBySpeakerIds($summit_id = 31) {

        $service = App::make(ISpeakerService::class);

        $summit_repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repo->getById($summit_id);

        $payload = [
            "email_flow_event"          => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED',
            "speaker_ids"               => [28297],
            "test_email_recipient"      => "smarcet@gmail.com",
            "outcome_email_recipient"   => "smarcet@gmail.com"
        ];

        $service->send($summit, $payload);

        $report = EmailExcerpt::getReport();

        $this->assertTrue(count($report) > 0);
        $this->assertTrue($report[0]['email_type'] == SpeakerAnnouncementSummitEmail::TypeAccepted);
    }
}