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

use App\Jobs\Emails\IMailTemplatesConstants;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use models\main\Member;
use models\summit\Presentation;
use services\model\ISubmitterService;
use utils\FilterParser;

/**
 * Class SubmitterServiceOriginalFilterTest
 *
 * SubmitterService::sendEmails re-parses payload["original_filter"] against its own
 * inline allow-list. A field missing there makes FilterParser::parse throw, the
 * catch nulls the whole filter, and the email silently falls back to the id== filter.
 * The queued email job builds its payload in the constructor, which still runs under
 * Queue::fake(), so the discarded scoping is observable on the pushed job.
 */
final class SubmitterServiceOriginalFilterTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        Config::set('cfp.base_url', 'http://cfp.test.example.com');
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testOriginalFilterWithPublishedFilterKeepsTrackScopingInTheEmail(): void
    {
        Queue::fake();

        $submitter = self::$em->find(Member::class, self::$member2->getId());

        // Same submitter, one published (= accepted) presentation per track.
        $this->seedAcceptedPresentation($submitter, self::$defaultTrack,   'Submitted In Default Track');
        $this->seedAcceptedPresentation($submitter, self::$secondaryTrack, 'Submitted In Secondary Track');
        self::$em->flush();

        // Mirrors summit-admin's "selected rows" send: ids go in `filter`,
        // the grid criteria travel in payload.original_filter (submitter-actions.js:313).
        $filter = FilterParser::parse(
            ['id==' . $submitter->getId()],
            ['id' => ['==']]
        );

        $payload = [
            'email_flow_event' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
            'should_resend'    => true,
            'original_filter'  => [
                'has_published_presentations==true',
                'presentations_track_id==' . self::$defaultTrack->getId(),
            ],
        ];

        App::make(ISubmitterService::class)->sendEmails(self::$summit->getId(), $payload, $filter);

        $jobs = Queue::pushed(PresentationSubmitterSelectionProcessAcceptedOnlyEmail::class);
        $this->assertCount(1, $jobs, 'exactly one submitter email must be queued');

        $emailPayload = $this->readPayload($jobs->first());
        $accepted     = $emailPayload[IMailTemplatesConstants::accepted_presentations];

        // With has_published_presentations missing from the service allow-list the
        // parse throws, original_filter is dropped whole, the id== filter takes over,
        // and the secondaryTrack presentation leaks into the email body.
        $this->assertCount(1, $accepted,
            'only the presentation in the filtered track belongs in the email');
        $this->assertSame(
            [self::$defaultTrack->getId()],
            array_values(array_unique(array_map(fn(array $p) => $p['track']['id'], $accepted))),
            'every listed presentation must belong to the track carried by original_filter'
        );
    }

    private function seedAcceptedPresentation(
        Member $submitter,
        $track,
        string $title
    ): Presentation {
        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle($title);
        $p->setAbstract('Abstract');
        $p->setCategory($track);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->setCreatedBy($submitter);
        $p->publish(); // published => "accepted" for Member::getAcceptedPresentations
        return $p;
    }

    /** AbstractEmailJob::$payload is protected and has no accessor. */
    private function readPayload(object $job): array
    {
        $prop = new \ReflectionProperty(\App\Jobs\Emails\AbstractEmailJob::class, 'payload');
        $prop->setAccessible(true);
        return $prop->getValue($job);
    }
}
