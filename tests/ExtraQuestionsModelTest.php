<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\Factories\SummitSelectionPlanExtraQuestionTypeFactory;

/**
 * Class ExtraQuestionsModelTest
 * @package Tests
 */
class ExtraQuestionsModelTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        self::clearTestData();
        parent::tearDown();
    }

    public function testAddSelectionPlanQuestion(){

        $newQuestion = SummitSelectionPlanExtraQuestionTypeFactory::build([
            'name' => 'Test Question',
            'label' => 'Test Question',
            'type' => ExtraQuestionTypeConstants::TextQuestionType,
            'placeholder' => 'This is a placeholder',
            'mandatory' => true,
        ]);

        self::$default_selection_plan->addExtraQuestion($newQuestion);
        self::$em->persist(self::$default_selection_plan);
        self::$em->flush();

        $this->assertFalse($newQuestion->getId() > 0);
    }

}