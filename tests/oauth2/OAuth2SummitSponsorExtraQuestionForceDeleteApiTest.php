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

use App\Jobs\EmitAuditLogJob;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\Services\Model\ISponsorUserInfoGrantService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use LaravelDoctrine\ORM\Facades\Registry;
use Mockery;
use models\main\SummitAuditLog;
use models\summit\SponsorBadgeScan;
use models\summit\SponsorBadgeScanExtraQuestionAnswer;
use models\utils\SilverstripeBaseModel;

/**
 * Class OAuth2SummitSponsorExtraQuestionForceDeleteApiTest
 * Sponsors can never delete an extra question or an answer option (devices may hold answers
 * the server has not seen); an admin can force it, with a reason, an explicit confirmation
 * when collected answers are destroyed, and an audit entry.
 * The current user is an administrator; the sponsor user cases live in OAuth2SummitSponsorApiTest.
 */
final class OAuth2SummitSponsorExtraQuestionForceDeleteApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    public function createApplication()
    {
        $app = parent::createApplication();

        $fileUploaderMock = Mockery::mock(\App\Http\Utils\IFileUploader::class)
            ->shouldIgnoreMissing();

        $fileUploaderMock->shouldReceive('build')->andReturn(new \models\main\File());

        $app->instance(\App\Http\Utils\IFileUploader::class, $fileUploaderMock);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    // ---- helpers ----

    /**
     * Sponsors are seeded with the maximum number of extra questions: makes room directly
     * on the model, then adds a question of the given type with the given option labels.
     * @param string $type
     * @param string[] $option_labels
     * @return array [question id, option ids]
     */
    private function addQuestion(string $type, array $option_labels = []): array
    {
        $sponsor = self::$sponsors[0];
        $sponsor->removeExtraQuestion($sponsor->getExtraQuestions()->last());
        self::$em->persist($sponsor);
        self::$em->flush();

        $question = new SummitSponsorExtraQuestionType();
        $question->setType($type);
        $question->setLabel('Force delete question');
        $question->setName('FORCE_DELETE_' . str_random(5));
        $values = [];
        foreach ($option_labels as $label) {
            $value = new ExtraQuestionTypeValue();
            $value->setLabel($label);
            $value->setValue($label);
            $question->addValue($value);
            $values[] = $value;
        }
        $sponsor->addExtraQuestion($question);
        self::$em->persist($sponsor);
        self::$em->flush();

        return [$question->getId(), array_map(fn($v) => $v->getId(), $values)];
    }

    /**
     * A scan of the sponsor that carries the given answer for the question.
     * @param int $question_id
     * @param string $answer
     * @param int $idx keeps the scans distinct
     * @return int scan id
     */
    private function addScanWithAnswer(int $question_id, string $answer, int $idx): int
    {
        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $service = App::make(ISponsorUserInfoGrantService::class);
        $scan = $service->addBadgeScan(self::$summit, self::$member, [
            'qr_code'         => $attendee->getFirstTicket()->getBadge()->generateQRCode(),
            'scan_date'       => 1572019200 + $idx,
            'sponsor_id'      => self::$sponsors[0]->getId(),
            'extra_questions' => [
                ['question_id' => $question_id, 'answer' => $answer],
            ],
        ]);
        return $scan->getId();
    }

    private function answersOf(int $question_id): array
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        $answers = $em->getRepository(SponsorBadgeScanExtraQuestionAnswer::class)
            ->createQueryBuilder('a')
            ->where('IDENTITY(a.question) = :q')
            ->setParameter('q', $question_id)
            ->getQuery()
            ->getResult();
        return $answers;
    }

    private function answerValueOfScan(int $scan_id): ?string
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        $scan = $em->getRepository(SponsorBadgeScan::class)->find($scan_id);
        $answer = $scan->getExtraQuestionAnswers()->first();
        return $answer === false ? null : $answer->getValue();
    }

    private function sponsorQuestionIds(): array
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        return $em->getRepository(SummitSponsorExtraQuestionType::class)
            ->createQueryBuilder('q')
            ->select('q.id')
            ->where('IDENTITY(q.sponsor) = :s')
            ->setParameter('s', self::$sponsors[0]->getId())
            ->getQuery()
            ->getSingleColumnResult();
    }

    private function deleteQuestion(int $question_id, array $query = [], ?array $body = null)
    {
        return $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestion",
            array_merge([
                'id'                => self::$summit->getId(),
                'sponsor_id'        => self::$sponsors[0]->getId(),
                'extra_question_id' => $question_id,
            ], $query),
            [],
            [],
            [],
            $this->getAuthHeaders(),
            is_null($body) ? null : json_encode($body)
        );
    }

    private function deleteValue(int $question_id, int $value_id, array $query = [], ?array $body = null)
    {
        return $this->action(
            "DELETE",
            "OAuth2SummitSponsorApiController@deleteExtraQuestionValue",
            array_merge([
                'id'                => self::$summit->getId(),
                'sponsor_id'        => self::$sponsors[0]->getId(),
                'extra_question_id' => $question_id,
                'value_id'          => $value_id,
            ], $query),
            [],
            [],
            [],
            $this->getAuthHeaders(),
            is_null($body) ? null : json_encode($body)
        );
    }

    /**
     * The force delete audit entry that was emitted, if any.
     * @return EmitAuditLogJob|null
     */
    private function emittedAudit(): ?EmitAuditLogJob
    {
        $jobs = Bus::dispatched(EmitAuditLogJob::class);
        return $jobs->isEmpty() ? null : $jobs->first();
    }

    /**
     * The force delete audit records persisted for the given target.
     * @param string $target_type question | option
     * @param int $target_id
     * @return SummitAuditLog[]
     */
    private function persistedAudits(string $target_type, int $target_id): array
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        $logs = $em->getRepository(SummitAuditLog::class)->findBy(['summit' => self::$summit->getId()]);
        return array_values(array_filter($logs, function (SummitAuditLog $log) use ($target_type, $target_id) {
            return str_ends_with((string)$log->getMetadata(), sprintf(' %s:%s', $target_type, $target_id));
        }));
    }

    protected function enableAuditCapture(): void
    {
        config(['opentelemetry.enabled' => true]);
        Bus::fake([EmitAuditLogJob::class]);
    }

    // ---- questions ----

    public function testAdminDeleteQuestionWithoutForceIsRefused()
    {
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);

        $response = $this->deleteQuestion($question_id);

        $this->assertResponseStatus(412);
        $this->assertStringContainsString("Questions can't be deleted once created", $response->getContent());
        $this->assertContains($question_id, $this->sponsorQuestionIds());
    }

    public function testAdminForceDeleteQuestionWithoutReasonIsRefused()
    {
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);

        $response = $this->deleteQuestion($question_id, ['force' => 'true']);
        $this->assertResponseStatus(412);
        $this->assertStringContainsString("A reason is required", $response->getContent());

        $this->deleteQuestion($question_id, ['force' => 'true'], ['reason' => '   ']);
        $this->assertResponseStatus(412);

        $this->assertContains($question_id, $this->sponsorQuestionIds());
    }

    public function testAdminForceDeleteUnknownQuestionIsNotFound()
    {
        $this->deleteQuestion(999999999, ['force' => 'true'], ['reason' => 'cleanup']);
        $this->assertResponseStatus(404);
    }

    public function testAdminForceDeleteQuestionWithoutAnswersFreesTheSlotAndIsAudited()
    {
        $this->enableAuditCapture();
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);
        // the sponsor is at the cap
        $this->assertCount(5, $this->sponsorQuestionIds());

        $this->deleteQuestion($question_id, ['force' => 'true'], ['reason' => 'created by mistake, 0 pending uploads']);

        $this->assertResponseStatus(204);
        $this->assertCount(4, $this->sponsorQuestionIds());
        $this->assertNotContains($question_id, $this->sponsorQuestionIds());

        $audit = $this->emittedAudit();
        $this->assertNotNull($audit);
        $this->assertEquals('created by mistake, 0 pending uploads', $audit->auditData['audit.reason']);
        $this->assertEquals((string)$question_id, $audit->auditData['audit.entity_id']);
        $this->assertEquals(0, $audit->auditData['audit.answers_deleted']);
        $this->assertEquals((string)self::$member->getId(), (string)$audit->auditData['auth.user.id']);

        $logs = $this->persistedAudits('question', $question_id);
        $this->assertCount(1, $logs);
        $this->assertEquals(self::$member->getId(), $logs[0]->getUser()->getId());
        $this->assertStringContainsString('Reason: created by mistake, 0 pending uploads', $logs[0]->getAction());

        // the slot is free right away
        $this->action(
            "POST",
            "OAuth2SummitSponsorApiController@addExtraQuestion",
            [
                'id'         => self::$summit->getId(),
                'sponsor_id' => self::$sponsors[0]->getId(),
            ],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'name'      => 'REPLACEMENT_' . str_random(5),
                'type'      => ExtraQuestionTypeConstants::TextQuestionType,
                'label'     => 'Replacement question',
                'mandatory' => false,
            ])
        );
        $this->assertResponseStatus(201);
        $this->assertCount(5, $this->sponsorQuestionIds());
    }

    public function testAdminForceDeleteQuestionWithAnswersIsRefusedWithTheirCount()
    {
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);
        $this->addScanWithAnswer($question_id, 'one', 1);
        $this->addScanWithAnswer($question_id, 'two', 2);
        $this->addScanWithAnswer($question_id, 'three', 3);

        $response = $this->deleteQuestion($question_id, ['force' => 'true'], ['reason' => 'sponsor insists']);

        $this->assertResponseStatus(412);
        $this->assertStringContainsString("3 collected answers will be permanently deleted", $response->getContent());
        $this->assertContains($question_id, $this->sponsorQuestionIds());
        $this->assertCount(3, $this->answersOf($question_id));
        // nothing was deleted, so nothing is audited
        $this->assertCount(0, $this->persistedAudits('question', $question_id));
    }

    public function testAdminForceDeleteQuestionWithAnswersAndConfirmationDestroysThemAndIsAudited()
    {
        $this->enableAuditCapture();
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);
        $scan_ids = [
            $this->addScanWithAnswer($question_id, 'one', 1),
            $this->addScanWithAnswer($question_id, 'two', 2),
            $this->addScanWithAnswer($question_id, 'three', 3),
        ];

        $this->deleteQuestion($question_id, ['force' => 'true', 'delete_answers' => 'true'], ['reason' => 'sponsor insists']);

        $this->assertResponseStatus(204);
        $this->assertNotContains($question_id, $this->sponsorQuestionIds());
        $this->assertCount(0, $this->answersOf($question_id));
        // the scans themselves are kept
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        foreach ($scan_ids as $scan_id) {
            $this->assertNotNull($em->getRepository(SponsorBadgeScan::class)->find($scan_id));
        }

        $audit = $this->emittedAudit();
        $this->assertNotNull($audit);
        $this->assertEquals(3, $audit->auditData['audit.answers_deleted']);
        $this->assertEquals('sponsor insists', $audit->auditData['audit.reason']);
    }

    public function testAdminForceDeleteQuestionIsPersistedInTheAuditLogWhenOpenTelemetryIsOff()
    {
        config(['opentelemetry.enabled' => false]);
        Bus::fake([EmitAuditLogJob::class]);
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);

        $this->deleteQuestion($question_id, ['force' => 'true'], ['reason' => 'created by mistake']);

        $this->assertResponseStatus(204);
        $this->assertNull($this->emittedAudit());
        $logs = $this->persistedAudits('question', $question_id);
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Reason: created by mistake', $logs[0]->getAction());
    }

    // ---- options ----

    public function testAdminDeleteOptionWithoutForceIsRefused()
    {
        [$question_id, [$a]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B']);

        $response = $this->deleteValue($question_id, $a);
        $this->assertResponseStatus(412);
        $this->assertStringContainsString("Answer options can't be deleted once created", $response->getContent());
    }

    public function testAdminForceDeleteOptionWithoutReasonIsRefused()
    {
        [$question_id, [$a]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B']);

        $this->deleteValue($question_id, $a, ['force' => 'true']);
        $this->assertResponseStatus(412);
    }

    public function testAdminForceDeleteUnusedOptionDeletesIt()
    {
        $this->enableAuditCapture();
        [$question_id, [$a, $b]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B']);
        // only B is used
        $this->addScanWithAnswer($question_id, (string)$b, 1);

        $this->deleteValue($question_id, $a, ['force' => 'true'], ['reason' => 'option created by mistake']);

        $this->assertResponseStatus(204);
        $audit = $this->emittedAudit();
        $this->assertNotNull($audit);
        $this->assertEquals(0, $audit->auditData['audit.answers_deleted']);
        $this->assertEquals(0, $audit->auditData['audit.answers_modified']);
        // B is untouched
        $this->assertCount(1, $this->answersOf($question_id));
    }

    public function testAdminForceDeleteUsedOptionIsRefusedWithTheCountOfDeletedAndModifiedAnswers()
    {
        [$question_id, [$a, $b, $c]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B', 'C']);
        $this->addScanWithAnswer($question_id, (string)$a, 1);             // only A: deleted
        $this->addScanWithAnswer($question_id, "$a,$b", 2);                // A and B: loses A
        $this->addScanWithAnswer($question_id, "$b,$c", 3);                // no A: untouched

        $response = $this->deleteValue($question_id, $a, ['force' => 'true'], ['reason' => 'sponsor insists']);

        $this->assertResponseStatus(412);
        $this->assertStringContainsString("2 collected answers use this option: 1 will be permanently deleted and 1 will only lose this option", $response->getContent());
        $this->assertCount(3, $this->answersOf($question_id));
    }

    public function testAdminForceDeleteUsedOptionWithConfirmationDeletesOnlyAnswersWithNoOtherOptionAndStripsTheRest()
    {
        $this->enableAuditCapture();
        [$question_id, [$a, $b, $c]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B', 'C']);
        $only_a  = $this->addScanWithAnswer($question_id, (string)$a, 1);
        $a_and_b = $this->addScanWithAnswer($question_id, "$a,$b", 2);
        $b_and_c = $this->addScanWithAnswer($question_id, "$b,$c", 3);
        $only_c  = $this->addScanWithAnswer($question_id, (string)$c, 4);

        $this->deleteValue($question_id, $a, ['force' => 'true', 'delete_answers' => 'true'], ['reason' => 'sponsor insists']);

        $this->assertResponseStatus(204);
        $this->assertNull($this->answerValueOfScan($only_a), 'the answer that only selected the option is deleted');
        $this->assertEquals((string)$b, $this->answerValueOfScan($a_and_b), 'the option id is taken out of the list');
        $this->assertEquals("$b,$c", $this->answerValueOfScan($b_and_c));
        $this->assertEquals((string)$c, $this->answerValueOfScan($only_c));

        $audit = $this->emittedAudit();
        $this->assertNotNull($audit);
        $this->assertEquals(1, $audit->auditData['audit.answers_deleted']);
        $this->assertEquals(1, $audit->auditData['audit.answers_modified']);
        $this->assertEquals('sponsor insists', $audit->auditData['audit.reason']);

        $logs = $this->persistedAudits('option', $a);
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Collected answers deleted: 1, modified: 1', $logs[0]->getAction());

        // the option is gone
        $this->deleteValue($question_id, $a, ['force' => 'true'], ['reason' => 'again']);
        $this->assertResponseStatus(404);
    }

    // ---- usage ----

    public function testAdminGetsQuestionUsage()
    {
        [$question_id] = $this->addQuestion(ExtraQuestionTypeConstants::TextQuestionType);
        $this->addScanWithAnswer($question_id, 'one', 1);
        $this->addScanWithAnswer($question_id, 'two', 2);

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getExtraQuestionUsage",
            [
                'id'                => self::$summit->getId(),
                'sponsor_id'        => self::$sponsors[0]->getId(),
                'extra_question_id' => $question_id,
            ],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $usage = json_decode($response->getContent());
        $this->assertEquals(2, $usage->answers_count);
        $this->assertEquals(1, $usage->reps_count);
        $this->assertEquals(self::$member->getId(), $usage->reps[0]->member_id);
        $this->assertEquals(2, $usage->reps[0]->scans_count);
        $this->assertGreaterThan(0, $usage->reps[0]->last_scan_date);
    }

    public function testAdminGetsOptionUsage()
    {
        [$question_id, [$a, $b]] = $this->addQuestion(ExtraQuestionTypeConstants::CheckBoxListQuestionType, ['A', 'B']);
        $this->addScanWithAnswer($question_id, (string)$a, 1);
        $this->addScanWithAnswer($question_id, "$a,$b", 2);
        $this->addScanWithAnswer($question_id, (string)$b, 3);

        $response = $this->action(
            "GET",
            "OAuth2SummitSponsorApiController@getExtraQuestionValueUsage",
            [
                'id'                => self::$summit->getId(),
                'sponsor_id'        => self::$sponsors[0]->getId(),
                'extra_question_id' => $question_id,
                'value_id'          => $a,
            ],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $usage = json_decode($response->getContent());
        $this->assertEquals(2, $usage->answers_count);
        $this->assertEquals(1, $usage->answers_to_delete);
        $this->assertEquals(1, $usage->answers_to_modify);
        $this->assertEquals(1, $usage->reps_count);
    }
}
