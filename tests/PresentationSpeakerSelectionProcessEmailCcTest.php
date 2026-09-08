<?php namespace Tests;
/*
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

use App\Jobs\Emails\IMailTemplatesConstants;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedOnlyEmail;
use App\Services\Utils\Email\SpeakersAnnouncementEmailConfigDTO;
use Illuminate\Support\Facades\Config;
use Mockery;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use ReflectionProperty;

/**
 * Policy Rule 9: the selection-process email is addressed to the speaker, but when the
 * "send copy to submitter" flag is on the very same payload is CC'd to the creators of the
 * speaker's presentations, who are non-admin third parties. The Member name fallback may
 * bypass the account visibility toggle only when the delivery is self-only.
 *
 * Constructing the job needs the full container (AbstractSummitEmailJob::__construct()
 * resolves ISummitRepository via App::make()), hence ProtectedApiTestCase + summit test data.
 *
 * @package Tests
 */
final class PresentationSpeakerSelectionProcessEmailCcTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    const SpeakerEmail = 'speaker@example.com';
    const MemberFullName = 'Ada Lovelace';

    /**
     * @var Presentation
     */
    static $presentation;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        Config::set('cfp.base_url', 'https://cfp.example.com');

        self::$presentation = new Presentation();
        self::$presentation->setTitle("SELECTION PROCESS CC TEST");
        self::$presentation->setType(self::$defaultPresentationType);
        self::$presentation->setSelectionPlan(self::$default_selection_plan);
        // the creator is who gets CC'd; must not be the speaker themself
        self::$presentation->setCreatedBy(self::$member);
        self::$presentation->setCategory(self::$defaultTrack);
        self::$summit->addEvent(self::$presentation);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * A speaker with no name of its own whose linked Member has the full-name toggle OFF:
     * the gated getter comes back empty, only the override surfaces the Member's name.
     */
    private function buildSpeakerRelyingOnMemberFallback(): PresentationSpeaker
    {
        /** @var PresentationSpeaker&\Mockery\MockInterface $speaker */
        $speaker = Mockery::mock(PresentationSpeaker::class);
        $speaker->shouldReceive('getId')->andReturn(1);
        $speaker->shouldReceive('getEmail')->andReturn(self::SpeakerEmail);
        $speaker->shouldReceive('getAcceptedPresentations')->andReturn([self::$presentation]);
        $speaker->shouldReceive('getAlternatePresentations')->andReturn([]);
        $speaker->shouldReceive('getRejectedPresentations')->andReturn([]);
        $speaker->shouldReceive('getFullName')->with(false)->andReturn('');
        $speaker->shouldReceive('getFullName')->with(true)->andReturn(self::MemberFullName);
        return $speaker;
    }

    private function buildJob(bool $send_copy_to_submitter): PresentationSpeakerSelectionProcessAcceptedOnlyEmail
    {
        $config = new SpeakersAnnouncementEmailConfigDTO();
        $config->setShouldSendCopy2Submitter($send_copy_to_submitter);

        return new PresentationSpeakerSelectionProcessAcceptedOnlyEmail(
            self::$summit,
            null,
            $this->buildSpeakerRelyingOnMemberFallback(),
            null,
            $config
        );
    }

    private function readPayload(PresentationSpeakerSelectionProcessAcceptedOnlyEmail $job): array
    {
        $prop = new ReflectionProperty($job, 'payload');
        $prop->setAccessible(true);
        return $prop->getValue($job);
    }

    public function testMemberNameIsNotSharedWithSubmitterCopies()
    {
        $payload = $this->readPayload($this->buildJob(true));

        $this->assertStringContainsString(self::$member->getEmail(), $payload[IMailTemplatesConstants::cc_email]);
        $this->assertSame(self::SpeakerEmail, $payload[IMailTemplatesConstants::speaker_full_name]);
    }

    public function testMemberNameIsUsedWhenDeliveryIsSelfOnly()
    {
        $payload = $this->readPayload($this->buildJob(false));

        $this->assertEmpty($payload[IMailTemplatesConstants::cc_email] ?? '');
        $this->assertSame(self::MemberFullName, $payload[IMailTemplatesConstants::speaker_full_name]);
    }
}
