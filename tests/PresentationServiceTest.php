<?php namespace Tests;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\App;
use models\exceptions\EntityNotFoundException;
use services\model\IPresentationService;

/**
 * Class PresentationServiceTest
 */
final class PresentationServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        // insertSummitTestData() already links a PresentationSpeaker to self::$defaultMember.
        // The concrete \models\oauth2\ResourceServerContext is final, so Mockery can't mock
        // it directly for facade replacement - mock the interface instead and rebind it under
        // the facade's accessor key.
        $resource_server_context_mock = \Mockery::mock(\models\oauth2\IResourceServerContext::class);
        $resource_server_context_mock->shouldReceive('getCurrentUser')->with(false)->andReturn(self::$defaultMember);
        App::instance('resource_server_context', $resource_server_context_mock);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * PresentationService::submitPresentation() (PresentationService.php:259) calls the
     * nested saveOrUpdatePresentation() (:399) with no try/catch. A nonexistent track_id
     * throws EntityNotFoundException at :443-450, rolling back the entire submission -
     * including the Presentation entity submitPresentation() itself already built and
     * attached to the summit BEFORE calling saveOrUpdatePresentation() (:362-368).
     */
    public function testSubmitPresentationRollsBackWhenTrackNotFound()
    {
        $service = App::make(IPresentationService::class);

        $title = 'Rollback Test Presentation ' . uniqid();

        $data = [
            'title' => $title,
            'description' => 'this is a description',
            'social_description' => 'this is a social description',
            'level' => 'N/A',
            'attendees_expected_learnt' => 'super duper',
            'type_id' => self::$defaultPresentationType->getId(),
            'track_id' => 999999999,
            'attending_media' => true,
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        try {
            $service->submitPresentation(self::$summit, $data);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach (self::$summit->getEvents() as $event) {
            $this->assertNotEquals($title, $event->getTitle());
        }
    }

    /**
     * PresentationService::updatePresentationSubmission() (PresentationService.php:502)
     * also calls the shared nested saveOrUpdatePresentation() with no try/catch. Prove the
     * update path rolls back too, leaving the target presentation's track/title unchanged.
     */
    public function testUpdatePresentationSubmissionRollsBackWhenTrackNotFound()
    {
        $service = App::make(IPresentationService::class);

        $original_title = 'Original Presentation ' . uniqid();

        $presentation = $service->submitPresentation(self::$summit, [
            'title' => $original_title,
            'description' => 'this is a description',
            'social_description' => 'this is a social description',
            'level' => 'N/A',
            'attendees_expected_learnt' => 'super duper',
            'type_id' => self::$defaultPresentationType->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'attending_media' => true,
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ]);
        $presentation_id = $presentation->getId();
        $original_track_id = $presentation->getCategoryId();

        try {
            $service->updatePresentationSubmission(self::$summit, $presentation_id, [
                'title' => 'Updated Title Should Not Persist',
                'type_id' => self::$defaultPresentationType->getId(),
                'track_id' => 999999999,
            ]);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
        }

        self::$em->clear();

        $reFetched = self::$em->find(\models\summit\Presentation::class, $presentation_id);
        $this->assertNotNull($reFetched);
        $this->assertEquals($original_title, $reFetched->getTitle());
        $this->assertEquals($original_track_id, $reFetched->getCategoryId());
    }
}
