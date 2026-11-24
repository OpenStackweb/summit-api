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

use App\Models\Foundation\Main\IGroup;
use App\Models\ResourceServer\IAccessTokenService;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Group;
use models\main\SummitAdministratorPermissionGroup;
use models\utils\SilverstripeBaseModel;

/**
 * The stream group at the HTTP level: an administrator adds a member whose only group is stream to
 * a summit's permission group, and that member then lists exactly the summits of their groups.
 *
 * self::$member is the administrator and the default caller. self::$member2 is the stream member:
 * the fixture creates it in summit-front-end-administrators, and setUp moves it to stream alone.
 * To call as the stream member the token stub is replaced, because its default IdP groups include
 * administrators, which would make GET /api/v1/summits/all return every summit.
 *
 * Class OAuth2SummitStreamGroupApiTest
 * @package Tests
 */
final class OAuth2SummitStreamGroupApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * @var int[]
     */
    private $permission_group_ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        // same steps as InsertMemberTestData::setMemberDefaultGroup, for the second member
        self::$em->remove(self::$group2);
        self::$group2 = new Group();
        self::$group2->setCode(IGroup::Stream);
        self::$group2->setTitle(IGroup::Stream);
        self::$em->persist(self::$group2);
        self::$member2->clearGroups();
        self::$member2->add2Group(self::$group2);
        self::$em->persist(self::$member2);
        self::$em->flush();

        $this->assertEquals([IGroup::Stream], self::$member2->getGroupsCodes(), 'fixture member must be on stream only');
        $this->assertFalse(self::$member2->isAdmin(true), 'fixture member must not be a global admin');
    }

    protected function tearDown(): void
    {
        // DoctrineMiddleware closes the manager after every request
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        $repository = self::$em->getRepository(SummitAdministratorPermissionGroup::class);
        foreach ($this->permission_group_ids as $id) {
            $group = $repository->find($id);
            if (!is_null($group))
                self::$em->remove($group);
        }
        self::$em->flush();
        $this->permission_group_ids = [];

        self::clearSummitTestData();
        parent::tearDown();
    }

    private function actAsTheStreamMember(): void
    {
        self::$service = new AccessTokenServiceStub([['slug' => IGroup::Stream]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });

        self::$service->setUserId(self::$member2->getUserExternalId());
        self::$service->setUserExternalId(self::$member2->getUserExternalId());
        self::$service->setUserEmail(self::$member2->getEmail());
        self::$service->setUserFirstName(self::$member2->getFirstName());
        self::$service->setUserLastName(self::$member2->getLastName());
    }

    private function getAllSummits()
    {
        return $this->action(
            "GET",
            "OAuth2SummitApiController@getAllSummits",
            ['relations' => 'none', 'expand' => 'none'],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
    }

    public function testAdministratorAddsAStreamMemberToAPermissionGroup()
    {
        $data = [
            'title' => 'TEST STREAM GROUP ' . str_random(16),
            'members' => [self::$member2->getId()],
            'summits' => [self::$summit->getId()],
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAdministratorPermissionGroupApiController@add",
            [],
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $group = json_decode($content);
        $this->assertNotNull($group);
        $this->permission_group_ids[] = $group->id;

        $this->assertEquals([self::$member2->getId()], array_map('intval', $group->members));
        $this->assertEquals([self::$summit->getId()], array_map('intval', $group->summits));
        $this->assertEquals([self::$summit->getId()], array_map('intval', self::$member2->getAllAllowedSummitsIds()));
    }

    public function testStreamMemberListsOnlyTheSummitsOfTheirPermissionGroups()
    {
        $group = new SummitAdministratorPermissionGroup();
        $group->setTitle('TEST STREAM GROUP ' . str_random(16));
        $group->addMember(self::$member2);
        $group->addSummit(self::$summit);
        self::$em->persist($group);
        self::$em->flush();
        $this->permission_group_ids[] = $group->getId();

        $this->actAsTheStreamMember();

        $response = $this->getAllSummits();

        $this->assertResponseStatus(200);
        $page = json_decode($response->getContent());
        $this->assertNotNull($page);
        // the fixture holds two summits, and the group is scoped to the first one
        $this->assertEquals(1, $page->total);
        $this->assertEquals(self::$summit->getId(), $page->data[0]->id);
    }

    public function testStreamMemberOnNoPermissionGroupIsRefused()
    {
        $this->actAsTheStreamMember();

        $this->getAllSummits();

        $this->assertResponseStatus(403);
    }
}
