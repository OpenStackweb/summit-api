<?php namespace Tests;
use models\summit\PresentationActionType;

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


/**
 * Class PresentationActionModelTest
 * @package Tests
 */
class PresentationActionModelTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    static $action1 = null;
    static $action2 = null;

    protected function setUp():void
    {
        parent::setUp();

        self::insertSummitTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testModelRelations(){

        self::$action1 = new PresentationActionType();
        self::$action1->setLabel("ACTION1");
        self::$action1->setOrder(1);
        self::$summit->addPresentationActionType(self::$action1);

        self::$action2 = new PresentationActionType();
        self::$action2->setLabel("ACTION2");
        self::$action2->setOrder(2);
        self::$summit->addPresentationActionType(self::$action2);

        self::$em->persist(self::$summit);
        self::$em->flush();

        //self::$summit->synchAllPresentationActions();

        foreach(self::$summit->getPresentations() as $presentation){
            $this->assertTrue(count($presentation->getPresentationActions()) == 2);
        }

    }
}