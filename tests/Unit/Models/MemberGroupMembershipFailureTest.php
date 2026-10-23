<?php namespace Tests\Unit\Models;
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

use App\Models\Foundation\Main\IGroup;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Mockery;
use models\main\Member;
use Tests\TestCase;

/**
 * Class MemberGroupMembershipFailureTest
 *
 * Member::belongsToGroup() must not cache the answer when the membership query fails: the lookup
 * answers "not a member" (fail closed) but a transient error cannot stick for the rest of the
 * request, so the next check runs the query again. No database needed: the raw SQL preparation
 * is stubbed on a partial mock.
 *
 * @package Tests\Unit\Models
 */
final class MemberGroupMembershipFailureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function membershipStatement(int $count): Statement
    {
        $result = Mockery::mock(Result::class);
        $result->shouldReceive('fetchFirstColumn')->andReturn([$count]);

        $stmt = Mockery::mock(Statement::class);
        $stmt->shouldReceive('executeQuery')->andReturn($result);

        return $stmt;
    }

    public function testFailedQueryIsNotCached(): void
    {
        $member = Mockery::mock(Member::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $calls = 0;
        $member->shouldReceive('prepareRawSQL')
            ->twice()
            ->andReturnUsing(function () use (&$calls) {
                if (++$calls === 1) {
                    throw new \Exception('connection lost');
                }
                return $this->membershipStatement(1);
            });

        // the failing lookup answers "not a member"...
        $this->assertFalse($member->belongsToGroup(IGroup::Administrators));
        // ...but is not remembered: the next check runs the query again and sees the real answer
        $this->assertTrue($member->belongsToGroup(IGroup::Administrators));
    }

    public function testSuccessfulQueryIsStillCached(): void
    {
        $member = Mockery::mock(Member::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $member->shouldReceive('prepareRawSQL')
            ->once()
            ->andReturn($this->membershipStatement(0));

        $this->assertFalse($member->belongsToGroup(IGroup::Administrators));
        $this->assertFalse($member->belongsToGroup(IGroup::Administrators));
    }
}
