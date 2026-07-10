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

use Illuminate\Support\Facades\App;
use models\exceptions\ValidationException;
use models\summit\ISpeakerRepository;
use services\model\ISpeakerService;

/**
 * Class SpeakerServiceRegistrationTest
 *
 * Kept separate from tests/SpeakerServiceTest.php: that class's 3 existing tests
 * hardcode external summit ids (3397/40/3611) and deliberately keep
 * InsertSummitTestData disabled, because insertSummitTestData() (InsertSummitTestData.php:300-310)
 * runs unscoped DELETE FROM Summit/Company/Sponsor/etc. with no restoration path -
 * reusing that class here would permanently wipe the data those tests depend on.
 */
final class SpeakerServiceRegistrationTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * SpeakerService::addSpeakerBySummit() (SpeakerService.php:300) calls addSpeaker()
     * (:193, its own nested transaction, creates the speaker) then, if a registration_code
     * is provided, registerSummitPromoCodeByValue() (:419, its own nested transaction) - no
     * try/catch around either call. If the code is already assigned to a DIFFERENT speaker
     * for the same summit (ValidationException at :439-441), the entire addSpeakerBySummit
     * call rolls back, including the speaker just created by the first nested call.
     */
    public function testAddSpeakerBySummitRollsBackEntireChainWhenRegistrationCodeAlreadyAssignedToAnotherSpeaker()
    {
        $service = App::make(ISpeakerService::class);

        $registration_code = 'REG-CODE-' . uniqid();

        // speaker A registers first, claiming the code
        $speaker_a_email = 'speaker-a-' . uniqid() . '@test.com';
        $service->addSpeakerBySummit(self::$summit, [
            'title' => 'Developer!',
            'first_name' => 'Speaker',
            'last_name' => 'A',
            'email' => $speaker_a_email,
            'registration_code' => $registration_code,
        ]);

        // speaker B tries to use the SAME code, already assigned to speaker A
        $speaker_b_email = 'speaker-b-' . uniqid() . '@test.com';

        try {
            $service->addSpeakerBySummit(self::$summit, [
                'title' => 'Developer!',
                'first_name' => 'Speaker',
                'last_name' => 'B',
                'email' => $speaker_b_email,
                'registration_code' => $registration_code,
            ]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $ex) {
            $this->assertStringContainsString('another speaker', $ex->getMessage());
        }

        self::$em->clear();

        $speaker_b = App::make(ISpeakerRepository::class)->getByEmail($speaker_b_email);
        $this->assertNull($speaker_b);
    }
}
