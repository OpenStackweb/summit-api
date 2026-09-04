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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessAcceptedAlternateEmail;
use App\Services\Model\Strategies\PromoCodes\IPromoCodeStrategy;
use App\Services\Model\Strategies\PromoCodes\IPromoCodeStrategyFactory;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use models\main\Member;
use models\summit\PresentationSpeaker;
use models\summit\SpeakerAnnouncementSummitEmail;
use ReflectionObject;
use ReflectionProperty;
use services\model\ISpeakerService;

/**
 * Covers SpeakerService::sendEmails()'s resume-skip check: on a retried chunk (payload carries
 * resume_since), a speaker whose proof for this email type was written at or after resume_since
 * is skipped - one INFO excerpt line, no promo code, no assistance - while every other speaker in
 * the chunk is processed exactly as a first attempt would. should_resend is independent of the
 * resume check: it keeps skipping any speaker with ANY historical proof regardless of date, on
 * both attempt shapes.
 *
 * No existing test class exercises sendEmails() directly - SpeakerServiceBulkSendChunkingTest is
 * scoped to triggerSendEmails's chunking/dispatch mechanics (Queue::fake() intercepts before a
 * chunk job's handle() ever runs), so this is a genuinely independent behavioral axis.
 *
 * Class SpeakerServiceResumeSendEmailsTest
 */
class SpeakerServiceResumeSendEmailsTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private const FLOW_EVENT = PresentationSpeakerSelectionProcessAcceptedAlternateEmail::EVENT_SLUG;
    private const EMAIL_TYPE = SpeakerAnnouncementSummitEmail::TypeAcceptedAlternate;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        // PresentationSpeakerSelectionProcessEmail::__construct throws InvalidArgumentException
        // when cfp.base_url is empty (its default in the test environment) - not gated behind
        // any resume logic, so every send in this class needs it set. Same fixture pattern as
        // PresentationReopenApiTest::seedCompletionEmailConfig().
        Config::set('cfp.base_url', 'https://testcfp.openstack.org');
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function service(): ISpeakerService
    {
        return App::make(ISpeakerService::class);
    }

    /**
     * The mail job (e.g. PresentationSpeakerSelectionProcessAcceptedAlternateEmail) exposes no
     * getSpeaker(); to_email is set from the speaker's email two constructors up
     * (AbstractSummitEmailJob -> AbstractEmailJob::$to_email), so walk the hierarchy the same way
     * ProcessSpeakersEmailRequestJobFailedHookTest::jobProperty() does.
     */
    private function jobProperty(object $job, string $name)
    {
        $reflection = new ReflectionObject($job);
        while ($reflection && !$reflection->hasProperty($name)) {
            $reflection = $reflection->getParentClass();
        }
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($job);
    }

    /**
     * @return PresentationSpeaker
     */
    private function newFixtureSpeaker(string $prefix): PresentationSpeaker
    {
        // A member is required so getEmail() (member wins, else registration request, else null)
        // resolves to a distinct, real address per speaker - the pushed job's to_email is how
        // these tests tell which speaker a dispatch targeted (the job exposes no getSpeaker()).
        // str_random suffix avoids a duplicate Member.Email collision across separate phpunit
        // invocations - InsertSummitTestData's cleanup does not roll back the Member table.
        $member = new Member();
        $member->setEmail("resume-test+{$prefix}-" . str_random(8) . "@test.com");
        $member->setActive(true);
        $member->setFirstName("Resume");
        $member->setLastName("Member {$prefix}");
        $member->setEmailVerified(true);
        $member->setUserExternalId(mt_rand());
        self::$em->persist($member);

        $speaker = new PresentationSpeaker();
        $speaker->setFirstName("Resume");
        $speaker->setLastName("Speaker {$prefix}");
        $speaker->setMember($member);
        self::$em->persist($speaker);

        $presentation = new \models\summit\Presentation();
        self::$summit->addEvent($presentation);
        $presentation->setTitle("Resume test presentation {$prefix}");
        $presentation->setAbstract("Abstract {$prefix}");
        $presentation->setCategory(self::$defaultTrack);
        $presentation->setType(self::$defaultPresentationType);
        $presentation->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $presentation->setEndDate(new \DateTime('+1 hour', new \DateTimeZone('UTC')));
        $presentation->addSpeaker($speaker);
        self::$em->persist($presentation);

        return $speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param \DateTime|null $backdateTo when null, the proof is stamped "now" (markAsSent());
     * otherwise its send_date is force-set via reflection, since production code has no setter
     * that backdates a proof (markAsSent() always stamps "now" by design).
     */
    private function givenSpeakerHasProof(PresentationSpeaker $speaker, ?\DateTime $backdateTo): void
    {
        $proof = new SpeakerAnnouncementSummitEmail();
        $proof->setType(self::EMAIL_TYPE);
        $speaker->addAnnouncementSummitEmail($proof);
        self::$summit->addAnnouncementSummitEmail($proof);

        if (is_null($backdateTo)) {
            $proof->markAsSent();
        } else {
            $prop = new ReflectionProperty(SpeakerAnnouncementSummitEmail::class, 'send_date');
            $prop->setAccessible(true);
            $prop->setValue($proof, $backdateTo);
        }
    }

    public function testResumedRunSkipsOnlySpeakerWithProofSinceDispatch(): void
    {
        Queue::fake();

        $speakerA = $this->newFixtureSpeaker('a-since-dispatch');
        $speakerB = $this->newFixtureSpeaker('b-before-dispatch');

        $dispatchedAt = time() - 600;

        $this->givenSpeakerHasProof($speakerA, null); // proof written "now", i.e. after dispatch
        $this->givenSpeakerHasProof($speakerB, new \DateTime('-30 days', new \DateTimeZone('UTC')));

        self::$em->flush();

        $this->service()->sendEmails(self::$summit->getId(), [
            'email_flow_event' => self::FLOW_EVENT,
            'speaker_ids'      => [$speakerA->getId(), $speakerB->getId()],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
        ], null);

        Queue::assertPushed(PresentationSpeakerSelectionProcessAcceptedAlternateEmail::class, 1);
        Queue::assertPushed(PresentationSpeakerSelectionProcessAcceptedAlternateEmail::class, function ($job) use ($speakerB) {
            return $this->jobProperty($job, 'to_email') === $speakerB->getEmail();
        });

        $skippedLines = array_values(array_filter(
            EmailExcerpt::getReport(),
            fn($line) => str_contains($line['message'] ?? '', $speakerA->getEmail())
        ));
        $this->assertCount(
            1,
            $skippedLines,
            'exactly one excerpt line must name the resume-skipped speaker A'
        );
    }

    public function testNonResumedRunWithShouldResendDefaultEmailsBoth(): void
    {
        Queue::fake();

        $speakerA = $this->newFixtureSpeaker('a-no-resume');
        $speakerB = $this->newFixtureSpeaker('b-no-resume');

        $this->givenSpeakerHasProof($speakerA, null);
        $this->givenSpeakerHasProof($speakerB, new \DateTime('-30 days', new \DateTimeZone('UTC')));

        self::$em->flush();

        $this->service()->sendEmails(self::$summit->getId(), [
            'email_flow_event' => self::FLOW_EVENT,
            'speaker_ids'      => [$speakerA->getId(), $speakerB->getId()],
        ], null);

        Queue::assertPushed(PresentationSpeakerSelectionProcessAcceptedAlternateEmail::class, 2);
    }

    public function testShouldResendFalseSkipsBothRegardlessOfResumeSince(): void
    {
        Queue::fake();

        $speakerA = $this->newFixtureSpeaker('a-should-resend-false');
        $speakerB = $this->newFixtureSpeaker('b-should-resend-false');

        $dispatchedAt = time() - 600;

        $this->givenSpeakerHasProof($speakerA, null);
        $this->givenSpeakerHasProof($speakerB, new \DateTime('-30 days', new \DateTimeZone('UTC')));

        self::$em->flush();

        $this->service()->sendEmails(self::$summit->getId(), [
            'email_flow_event' => self::FLOW_EVENT,
            'speaker_ids'      => [$speakerA->getId(), $speakerB->getId()],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
            'should_resend'    => false,
        ], null);

        Queue::assertNotPushed(PresentationSpeakerSelectionProcessAcceptedAlternateEmail::class);
    }

    public function testResumedRunNeverInvokesGetPromoCodeForSkippedSpeaker(): void
    {
        Queue::fake();

        $speakerA = $this->newFixtureSpeaker('a-promo-skip');
        $speakerB = $this->newFixtureSpeaker('b-promo-send');

        $dispatchedAt = time() - 600;

        $this->givenSpeakerHasProof($speakerA, null);
        $this->givenSpeakerHasProof($speakerB, new \DateTime('-30 days', new \DateTimeZone('UTC')));

        self::$em->flush();

        // A strict shouldReceive(...)->once()->withArgs(...) expectation would look right but
        // never actually fail this test: SpeakerService::sendEmails wraps the per-speaker closure
        // in catch (\Exception $ex) { Log::warning($ex); ... }, and Mockery's
        // NoMatchingExpectationException extends \Exception - a mismatched call gets swallowed
        // and logged, not surfaced as a test failure. Recording calls and asserting afterward
        // sidesteps that; verified empirically by removing the resume-skip fix.
        $calledWith = [];
        $promoCodeStrategy = Mockery::mock(IPromoCodeStrategy::class);
        $promoCodeStrategy->shouldReceive('getPromoCode')
            ->andReturnUsing(function (PresentationSpeaker $speaker) use (&$calledWith) {
                $calledWith[] = $speaker->getId();
                return null;
            });

        $promoCodeStrategyFactory = Mockery::mock(IPromoCodeStrategyFactory::class);
        $promoCodeStrategyFactory->shouldReceive('createStrategy')->andReturn($promoCodeStrategy);
        App::instance(IPromoCodeStrategyFactory::class, $promoCodeStrategyFactory);
        // ISpeakerService is bound as a singleton (ModelServicesProvider): once resolved, its
        // constructor-injected IPromoCodeStrategyFactory is fixed. Forget the cached instance so
        // service() below rebuilds it against the rebind above, or this mock is silently ignored
        // and the real factory (and real promo-code side effects) runs instead.
        App::forgetInstance(ISpeakerService::class);

        $this->service()->sendEmails(self::$summit->getId(), [
            'email_flow_event' => self::FLOW_EVENT,
            'speaker_ids'      => [$speakerA->getId(), $speakerB->getId()],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
            'promo_code_spec'  => ['type' => 'automatic'],
        ], null);

        $this->assertEquals(
            [$speakerB->getId()],
            $calledWith,
            'getPromoCode() must be called exactly once, for speaker B only - never for the resume-skipped speaker A'
        );
    }
}
