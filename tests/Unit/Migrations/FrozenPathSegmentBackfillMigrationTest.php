<?php namespace Tests\Unit\Migrations;
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

use Database\Migrations\Model\Version20261014120001;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Mockery;
use models\summit\Presentation;
use PHPUnit\Framework\TestCase;

class FrozenPathSegmentBackfillMigrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function migrationWithRows(array $rows): Version20261014120001
    {
        // Constructor bypassed: it needs a configured Connection and logger. Property defaults
        // (including AbstractMigration's $plannedSql = [] and $frozen = false) still apply.
        $migration = (new \ReflectionClass(Version20261014120001::class))->newInstanceWithoutConstructor();
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('fetchAllAssociative')->once()->andReturn($rows);
        $p = new \ReflectionProperty(\Doctrine\Migrations\AbstractMigration::class, 'connection');
        $p->setAccessible(true);
        $p->setValue($migration, $conn);
        return $migration;
    }

    public function testUsesTitleSlugWhenTitlePresent(): void
    {
        $m = $this->migrationWithRows([[
            'ID' => 5, 'SummitID' => 12, 'SummitSlug' => 'summit-2020',
            'PresentationID' => 300, 'Title' => 'My Talk: Part 1', 'PresentationSlug' => 'stale-slug',
        ]]);
        $m->up(new Schema());
        $sql = $m->getSql();
        $this->assertCount(1, $sql);
        $this->assertStringContainsString('UPDATE PresentationMediaUpload SET FrozenPathSegment = ?', $sql[0]->getStatement());
        $this->assertSame(
            ['12-summit-2020/300-' . filter_var(Presentation::slugify('My Talk: Part 1'), FILTER_SANITIZE_ENCODED), 5],
            $sql[0]->getParameters()
        );
    }

    public function testFallsBackToStoredSlugWhenTitleEmpty(): void
    {
        $m = $this->migrationWithRows([[
            'ID' => 6, 'SummitID' => 8, 'SummitSlug' => 's8',
            'PresentationID' => 301, 'Title' => '', 'PresentationSlug' => 'kept-slug',
        ]]);
        $m->up(new Schema());
        $sql = $m->getSql();
        $this->assertSame(['8-s8/301-kept-slug', 6], $sql[0]->getParameters());
    }

    public function testNoRowsNoSql(): void
    {
        $m = $this->migrationWithRows([]);
        $m->up(new Schema());
        $this->assertSame([], $m->getSql());
    }
}
