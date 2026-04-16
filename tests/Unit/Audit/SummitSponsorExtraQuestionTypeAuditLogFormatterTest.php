<?php namespace Tests\Unit\Audit;
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

use App\Audit\ConcreteFormatters\SummitSponsorExtraQuestionTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use models\main\Company;
use models\summit\Sponsor;
use Tests\TestCase;

/**
 * Regression tests for SummitSponsorExtraQuestionTypeAuditLogFormatter.
 *
 * Reproduces the production TypeError that occurred when deleting a
 * SummitSponsorExtraQuestionType:
 *
 *   Sponsor::removeExtraQuestion() calls $extra_question->clearSponsor(),
 *   which nulls the sponsor in-memory. On flush, AuditEventListener invokes
 *   this formatter, which calls $subject->getSponsor(). Before the fix,
 *   that method was typed `: Sponsor` (non-nullable) and threw TypeError
 *   because PHP refused to return null under a non-null return type.
 *
 *   TypeError escaped the formatter's try/catch because it catches
 *   \Exception, but TypeError extends \Error (not \Exception), so the
 *   500 reached the user.
 *
 * The fix widens getSponsor() to `: ?Sponsor`. These tests lock in that
 * behavior: after clearSponsor() the formatter must return a non-null
 * string without throwing, and fall back to the "Unknown Sponsor" label.
 *
 * @package Tests\Unit\Audit
 */
final class SummitSponsorExtraQuestionTypeAuditLogFormatterTest extends TestCase
{
    private const LABEL       = 'What is your T-shirt size?';
    private const QUESTION_ID = 950;
    private const SPONSOR_ID  = 610;

    /**
     * Build a SummitSponsorExtraQuestionType with the sponsor explicitly
     * cleared - mirroring what Sponsor::removeExtraQuestion() does on the
     * deletion path that caused the original production TypeError.
     */
    private function makeQuestionWithClearedSponsor(): SummitSponsorExtraQuestionType
    {
        $company = new Company();
        $company->setName('Acme Corp');

        $sponsor = new Sponsor();
        $sponsor->setCompany($company);
        $this->setProtectedId($sponsor, self::SPONSOR_ID);

        $question = new SummitSponsorExtraQuestionType();
        $question->setName('tshirt_size');
        $question->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $question->setLabel(self::LABEL);
        $question->setSponsor($sponsor);
        $this->setProtectedId($question, self::QUESTION_ID);

        // Simulate what Sponsor::removeExtraQuestion() does: it calls
        // clearSponsor() on the extra question to cut the bidirectional link
        // BEFORE the flush reaches the audit listener.
        $question->clearSponsor();

        return $question;
    }

    /**
     * Build a SummitSponsorExtraQuestionType with an attached sponsor
     * (happy path - sanity check that the formatter still works).
     */
    private function makeQuestionWithSponsor(): SummitSponsorExtraQuestionType
    {
        $company = new Company();
        $company->setName('Acme Corp');

        $sponsor = new Sponsor();
        $sponsor->setCompany($company);
        $this->setProtectedId($sponsor, self::SPONSOR_ID);

        $question = new SummitSponsorExtraQuestionType();
        $question->setName('tshirt_size');
        $question->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $question->setLabel(self::LABEL);
        $question->setSponsor($sponsor);
        $this->setProtectedId($question, self::QUESTION_ID);

        return $question;
    }

    /**
     * BaseEntity::$id is protected with no public setter; use reflection
     * so the formatter sees a stable numeric id in its output.
     */
    private function setProtectedId(object $entity, int $id): void
    {
        $rc = new \ReflectionClass($entity);
        while ($rc !== false && !$rc->hasProperty('id')) {
            $rc = $rc->getParentClass();
        }
        if ($rc === false) {
            self::fail('Could not locate $id property on ' . get_class($entity));
        }
        $prop = $rc->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($entity, $id);
    }

    /**
     * Reproduces the exact production failure: deletion flow where
     * clearSponsor() has been invoked before the audit listener runs.
     *
     * Before the fix: TypeError from getSponsor() escaped through the
     * try/catch(\Exception) and propagated up to the HTTP handler.
     *
     * After the fix: formatter returns a non-null string with the
     * "Unknown Sponsor" fallback.
     */
    public function test_deletion_format_does_not_crash_when_sponsor_is_cleared(): void
    {
        $question  = $this->makeQuestionWithClearedSponsor();
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_DELETION
        );

        $result = $formatter->format($question, []);

        self::assertIsString($result, 'Formatter must not throw or return null on deletion with cleared sponsor');
        self::assertStringContainsString(self::LABEL, $result);
        self::assertStringContainsString('(ID: ' . self::QUESTION_ID . ')', $result);
        self::assertStringContainsString('Unknown Sponsor', $result);
        self::assertStringContainsString('deleted by user', $result);
    }

    /**
     * The audit formatter is also invoked on updates; ensure the same
     * null-safe path is taken there.
     */
    public function test_update_format_does_not_crash_when_sponsor_is_cleared(): void
    {
        $question  = $this->makeQuestionWithClearedSponsor();
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_UPDATE
        );

        $result = $formatter->format($question, []);

        self::assertIsString($result);
        self::assertStringContainsString(self::LABEL, $result);
        self::assertStringContainsString('Unknown Sponsor', $result);
        self::assertStringContainsString('updated', $result);
    }

    /**
     * Defensive: creation formatting must also tolerate a null sponsor.
     */
    public function test_creation_format_does_not_crash_when_sponsor_is_cleared(): void
    {
        $question  = $this->makeQuestionWithClearedSponsor();
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_CREATION
        );

        $result = $formatter->format($question, []);

        self::assertIsString($result);
        self::assertStringContainsString(self::LABEL, $result);
        self::assertStringContainsString('Unknown Sponsor', $result);
        self::assertStringContainsString('created by user', $result);
    }

    /**
     * Happy-path sanity: when the sponsor is attached the formatter
     * renders the company name and sponsor id (proves the null-safety
     * fix didn't regress the normal output format).
     */
    public function test_deletion_format_includes_company_and_sponsor_id_when_sponsor_present(): void
    {
        $question  = $this->makeQuestionWithSponsor();
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_DELETION
        );

        $result = $formatter->format($question, []);

        self::assertIsString($result);
        self::assertStringContainsString('Acme Corp', $result);
        self::assertStringContainsString('(ID: ' . self::SPONSOR_ID . ')', $result);
        self::assertStringNotContainsString('Unknown Sponsor', $result);
    }

    /**
     * Guards the early-return: formatter must ignore subjects of other
     * types without throwing.
     */
    public function test_format_returns_null_for_unrelated_subject(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_DELETION
        );

        self::assertNull($formatter->format(new \stdClass(), []));
    }
}
