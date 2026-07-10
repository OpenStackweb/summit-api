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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use Illuminate\Support\Facades\App;
use models\exceptions\EntityNotFoundException;

/**
 * Class SelectionPlanOrderExtraQuestionTypeServiceTest
 */
final class SelectionPlanOrderExtraQuestionTypeServiceTest extends BrowserKitTestCase
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
     * SelectionPlanOrderExtraQuestionTypeService::updateExtraQuestionBySelectionPlan()
     * (SelectionPlanOrderExtraQuestionTypeService.php:117) calls the nested updateExtraQuestion()
     * (:89) with no try/catch. updateExtraQuestion() commits its label change BEFORE the outer's
     * own assignment check runs - a question added to the summit only (via addExtraQuestion(),
     * which never assigns it to a selection plan) but never assigned to the target selection plan
     * makes the outer's getAssignedExtraQuestion() check fail AFTER the inner already committed,
     * rolling back that already-committed label change too.
     */
    public function testUpdateExtraQuestionBySelectionPlanRollsBackAlreadyCommittedLabelWhenNotAssignedToPlan()
    {
        $service = App::make(ISelectionPlanExtraQuestionTypeService::class);

        $original_label = 'Original Label ' . uniqid();

        $question = $service->addExtraQuestion(self::$summit, [
            'name' => 'Q_' . uniqid(),
            'label' => $original_label,
            'type' => ExtraQuestionTypeConstants::TextQuestionType,
        ]);
        $question_id = $question->getId();

        try {
            $service->updateExtraQuestionBySelectionPlan(self::$default_selection_plan, $question_id, [
                'label' => 'Updated Label ' . uniqid(),
            ]);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('does not belongs to selection plan', $ex->getMessage());
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());
        $reFetched = self::$summit->getSelectionPlanExtraQuestionById($question_id);
        $this->assertNotNull($reFetched);
        $this->assertEquals($original_label, $reFetched->getLabel());
    }
}
