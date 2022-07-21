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
use Illuminate\Support\Facades\Request;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SpeakerAnnouncementSummitEmail;
use services\model\ISpeakerService;
use utils\FilterParser;

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

    public function testSendSpeakerEmailsPerSelectionPlanAndOnlyAccepted($summit_id = 40) {

        $service = App::make(ISpeakerService::class);

        $summit_repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repo->getById($summit_id);

        $filterParam = [
          'presentations_selection_plan_id==23',
          'has_rejected_presentations==false',
          'has_accepted_presentations==true',
          'has_alternate_presentations==false'
        ];

        $filter = FilterParser::parse($filterParam,
            [
                'first_name' => ['=@', '@@', '=='],
                'last_name' => ['=@', '@@', '=='],
                'email' => ['=@', '@@', '=='],
                'id' => ['=='],
                'full_name' => ['=@', '@@', '=='],
                'has_accepted_presentations' => ['=='],
                'has_alternate_presentations' => ['=='],
                'has_rejected_presentations' => ['=='],
                'presentations_track_id' => ['=='],
                'presentations_selection_plan_id' => ['=='],
                'presentations_type_id' => ['=='],
                'presentations_title' => ['=@', '@@', '=='],
                'presentations_abstract' => ['=@', '@@', '=='],
                'presentations_submitter_full_name' => ['=@', '@@', '=='],
                'presentations_submitter_email' => ['=@', '@@', '=='],
            ]
        );

        $payload = [
            "email_flow_event"          => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED',
            "test_email_recipient"      => "smarcet@gmail.com",
            "should_send_copy_2_submitter" => false,
            "outcome_email_recipient"   => "smarcet@gmail.com"
        ];

        $service->sendEmails($summit->getId(), $payload, $filter);

        $report = EmailExcerpt::getReport();

        $this->assertTrue(count($report) > 0);
        $this->assertTrue($report[0]['email_type'] == SpeakerAnnouncementSummitEmail::TypeAccepted);
    }

    public function testSendSpeakerEmailsPerSelectionPlanAndAcceptedRejected($summit_id = 40) {

        $service = App::make(ISpeakerService::class);

        $summit_repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $summit_repo->getById($summit_id);

        $filterParam = [
            'presentations_selection_plan_id==23',
            'has_rejected_presentations==true',
            'has_accepted_presentations==true',
            'has_alternate_presentations==false'
        ];

        $filter = FilterParser::parse($filterParam,
            [
                'first_name' => ['=@', '@@', '=='],
                'last_name' => ['=@', '@@', '=='],
                'email' => ['=@', '@@', '=='],
                'id' => ['=='],
                'full_name' => ['=@', '@@', '=='],
                'has_accepted_presentations' => ['=='],
                'has_alternate_presentations' => ['=='],
                'has_rejected_presentations' => ['=='],
                'presentations_track_id' => ['=='],
                'presentations_selection_plan_id' => ['=='],
                'presentations_type_id' => ['=='],
                'presentations_title' => ['=@', '@@', '=='],
                'presentations_abstract' => ['=@', '@@', '=='],
                'presentations_submitter_full_name' => ['=@', '@@', '=='],
                'presentations_submitter_email' => ['=@', '@@', '=='],
            ]
        );

        $payload = [
            "email_flow_event"          => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_REJECTED',
            "test_email_recipient"      => "smarcet@gmail.com",
            "should_send_copy_2_submitter" => false,
            "outcome_email_recipient"   => "smarcet@gmail.com"
        ];

        $service->sendEmails($summit->getId(), $payload, $filter);

        $report = EmailExcerpt::getReport();

        $this->assertTrue(count($report) > 0);
        $this->assertTrue($report[0]['email_type'] == SpeakerAnnouncementSummitEmail::TypeAccepted);
    }

}