<?php namespace Tests;
/**
 * Copyright 2024 OpenStack Foundation
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

use App\Jobs\Emails\RegisteredMemberOrderPaidMail;
use ReflectionObject;

/**
 * Class AbstractSummitEmailJobTest
 */
final class AbstractSummitEmailJobTest extends TestCase
{
    use InsertSummitTestData;

    use InsertOrdersTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testMarketingVariablesInjection() {
        $job = new RegisteredMemberOrderPaidMail(self::$summit_orders[0]);

        $p = (new ReflectionObject($job))->getProperty('payload');

        $p->setAccessible(true);

        $payload = $p->getValue($job);

        $keys = array_keys($payload);

        $result = preg_grep('/EMAIL_TEMPLATE_.+/i', $keys);

        $this->assertNotEmpty($result);
    }
}