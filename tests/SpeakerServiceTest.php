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
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use services\model\ISpeakerService;

/**
 * Class SpeakerServiceTest
 */
final class SpeakerServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::insertTestData();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testSendAcceptedAlternateEmailBySpeakerIds($summit_id = 1723) {

        $service = App::make(ISpeakerService::class);

        $summit_repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repo->getById($summit_id);

        $payload = [
            "email_flow_event"          => PresentationSpeakerSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
            "speaker_ids"               => [29350],
            "test_email_recipient"      => self::$member->getEmail(),
            "outcome_email_recipient"   => self::$member2->getEmail(),
        ];

        $service->send($summit, $payload);
    }
}