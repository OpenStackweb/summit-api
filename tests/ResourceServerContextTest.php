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
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\utils\SilverstripeBaseModel;
/**
 * Class ResourceServerContextTest
 * @package Tests
 */
class ResourceServerContextTest extends BrowserKitTestCase
{
    /**
     * Minimal IDP auth-context claims for a user, mirroring testSync's shape.
     * user_groups uses the REAL claim shape (array of ['slug' => ...] objects) -
     * checkGroups() reads $idpGroup['slug'].
     */
    private function buildAuthContext(string $external_id, string $email, string $first_name = 'test', string $last_name = 'test', array $groups = []): array
    {
        return [
            'user_id'             => $external_id,
            'external_user_id'    => $external_id,
            'user_identifier'     => 'test',
            'user_email'          => $email,
            'user_email_verified' => true,
            'user_first_name'     => $first_name,
            'user_last_name'      => $last_name,
            'user_groups'         => $groups,
        ];
    }

    private function persistMember(string $email, string $first_name, string $last_name, ?string $external_id = null): Member
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $member = new Member();
        $member->setEmail($email);
        $member->setActive(true);
        $member->setFirstName($first_name);
        $member->setLastName($last_name);
        $member->setEmailVerified(true);
        // Member.ExternalUserId carries a unique index with default 0, so every
        // member needs SOME unique external id (the same pattern InsertMemberTestData
        // uses); pass one explicitly when the test needs to control it.
        $member->setUserExternalId(is_null($external_id) ? mt_rand() : intval($external_id));
        $em->persist($member);
        $em->flush();
        return $member;
    }

    public function testSync(){
        $ctx = App::make(IResourceServerContext::class);
        $this->assertInstanceOf(IResourceServerContext::class, $ctx);

        $context = [];
        $context['user_id'] = "1080";
        $context['external_user_id'] = "1080";
        $context['user_identifier']  = "test";
        $context['user_email']       = "test@test.com";
        $context['user_email_verified'] = true;
        $context['user_first_name']  = "test";
        $context['user_last_name']   = "test";
        $context['user_groups']      = ['raw-users'];
        $ctx->setAuthorizationContext($context);

        $member = $ctx->getCurrentUser(true);

        // A member is resolved/created from the IDP auth-context claims...
        $this->assertNotNull($member, 'getCurrentUser must resolve a member from the auth context');
        // ...and the claim fields are synced onto it.
        $this->assertEquals($context['user_email'], $member->getEmail());
        $this->assertEquals($context['user_first_name'], $member->getFirstName());
        $this->assertEquals($context['user_last_name'], $member->getLastName());

        // Request-scoped cache: a second call returns the identical instance.
        $this->assertSame(
            $member,
            $ctx->getCurrentUser(true),
            'getCurrentUser must return the cached instance within a request'
        );
    }

    public function testSetAuthorizationContextResetsUserCache(): void
    {
        $ctx = App::make(IResourceServerContext::class);

        $context = [];
        $context['user_id']             = "1080";
        $context['external_user_id']    = "1080";
        $context['user_identifier']     = "test";
        $context['user_email']          = "test@test.com";
        $context['user_email_verified'] = true;
        $context['user_first_name']     = "test";
        $context['user_last_name']      = "test";
        $context['user_groups']         = ['raw-users'];
        $ctx->setAuthorizationContext($context);
        $ctx->getCurrentUser(false); // warm the request-scoped cache

        $ref  = new \ReflectionClass($ctx);
        $prop = $ref->getProperty('cachedCurrentUserResolved');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($ctx),
            'Prerequisite: cache must be warm after the first getCurrentUser() call');

        // A second setAuthorizationContext() must invalidate the cache so the
        // next getCurrentUser() re-fetches instead of returning the stale member.
        $ctx->setAuthorizationContext($context);
        $this->assertFalse($prop->getValue($ctx),
            'setAuthorizationContext() must reset cachedCurrentUserResolved');
    }

    /**
     * No user_id claim => anonymous request: getCurrentUser() must return null and
     * cache that null so subsequent calls don't re-attempt resolution.
     */
    public function testGetCurrentUserReturnsNullForAnonymousRequest(): void
    {
        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext([]);

        $this->assertNull($ctx->getCurrentUser());

        $ref  = new \ReflectionClass($ctx);
        $prop = $ref->getProperty('cachedCurrentUserResolved');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($ctx), 'The null result must be cached');

        $this->assertNull($ctx->getCurrentUser(), 'The cached null must be returned as-is');
    }

    /**
     * Lookup strategy 1: an existing member linked to the IDP external id is
     * resolved directly.
     */
    public function testGetCurrentUserResolvesExistingMemberByExternalId(): void
    {
        $external_id = (string)mt_rand(100000000, 999999999);
        $email = sprintf('ctx-ext-%s@test.com', uniqid());
        $existing = $this->persistMember($email, 'Existing', 'ByExternalId', $external_id);

        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext($this->buildAuthContext($external_id, $email, 'Existing', 'ByExternalId'));

        $member = $ctx->getCurrentUser();

        $this->assertNotNull($member);
        $this->assertEquals($existing->getId(), $member->getId(), 'Must resolve the pre-existing member, not create a new one');
    }

    /**
     * Lookup strategy 2: a member that exists locally by email but is not linked
     * to THIS IDP external id is resolved by email - and the claim's external id
     * gets linked as a side-effect.
     */
    public function testGetCurrentUserResolvesExistingMemberByEmailAndLinksExternalId(): void
    {
        $email = sprintf('ctx-email-%s@test.com', uniqid());
        $existing = $this->persistMember($email, 'Existing', 'ByEmail');

        $external_id = (string)mt_rand(100000000, 999999999);
        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext($this->buildAuthContext($external_id, $email, 'Existing', 'ByEmail'));

        $member = $ctx->getCurrentUser();

        $this->assertNotNull($member);
        $this->assertEquals($existing->getId(), $member->getId(), 'Must resolve the pre-existing member by email');
        $this->assertEquals(intval($external_id), $member->getUserExternalId(), 'The IDP external id must be linked as a side-effect');
    }

    /**
     * A first call with $update_member_fields=false must NOT apply the IDP claim
     * fields; a later call with true within the same request must apply them
     * exactly once (PHASE A deferred sync) - and the cache must survive the sync
     * (Member::setFirstName() invalidates it mid-flight, getCurrentUser() restores it).
     */
    public function testGetCurrentUserDefersFieldSyncUntilRequested(): void
    {
        $external_id = (string)mt_rand(100000000, 999999999);
        $email = sprintf('ctx-defer-%s@test.com', uniqid());
        $this->persistMember($email, 'OldFirst', 'OldLast', $external_id);

        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext($this->buildAuthContext($external_id, $email, 'NewFirst', 'NewLast'));

        $member = $ctx->getCurrentUser(true, false);
        $this->assertNotNull($member);
        $this->assertEquals('OldFirst', $member->getFirstName(), 'update_member_fields=false must not sync claim fields');
        $this->assertEquals('OldLast', $member->getLastName());

        $member = $ctx->getCurrentUser(true, true);
        $this->assertNotNull($member, 'The cache must survive the deferred field sync');
        $this->assertEquals('NewFirst', $member->getFirstName(), 'The deferred field sync must apply the claim fields');
        $this->assertEquals('NewLast', $member->getLastName());

        $this->assertNotNull($ctx->getCurrentUser(true, true), 'Subsequent calls must keep returning the cached member');
    }

    /**
     * A first call with $synch_groups=false must NOT reconcile IDP groups; a later
     * call with true within the same request must (PHASE A deferred group sync).
     * Uses the REAL claim shape ([['slug' => ...]]).
     */
    public function testGetCurrentUserDefersGroupSyncUntilRequested(): void
    {
        $external_id = (string)mt_rand(100000000, 999999999);
        $email = sprintf('ctx-groups-%s@test.com', uniqid());
        $this->persistMember($email, 'Group', 'Sync', $external_id);

        $slug = sprintf('idp-group-%s', uniqid());
        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext(
            $this->buildAuthContext($external_id, $email, 'Group', 'Sync', [['slug' => $slug]])
        );

        $member = $ctx->getCurrentUser(false);
        $this->assertNotNull($member);
        $this->assertFalse($member->belongsToGroup($slug), 'synch_groups=false must not reconcile IDP groups');
        $member_id = $member->getId();

        $member = $ctx->getCurrentUser(true);
        $this->assertNotNull($member);

        // Asserting on the SAME instance also pins the belongsToGroup() memo
        // invalidation: the pre-sync assert above memoized `false` for this slug,
        // and add2Group() must clear that memo so this re-check hits the DB.
        $this->assertTrue($member->belongsToGroup($slug), 'The deferred group sync must add the IDP group');
        $this->assertEquals($member_id, $member->getId());
    }

    /**
     * Email collision guard: when the resolved member's new claim email is already
     * held by a DIFFERENT member, that other member's email must be invalidated
     * (the IDP is the source of truth) and the claim email applied to the
     * resolved member.
     */
    public function testGetCurrentUserInvalidatesEmailOfCollidingMember(): void
    {
        $external_id = (string)mt_rand(100000000, 999999999);
        $old_email = sprintf('ctx-collision-a-%s@test.com', uniqid());
        $resolved = $this->persistMember($old_email, 'Colliding', 'Owner', $external_id);

        $claimed_email = sprintf('ctx-collision-b-%s@test.com', uniqid());
        $other = $this->persistMember($claimed_email, 'Other', 'Holder');
        $other_id = $other->getId();

        $ctx = App::make(IResourceServerContext::class);
        $ctx->setAuthorizationContext($this->buildAuthContext($external_id, $claimed_email, 'Colliding', 'Owner'));

        $member = $ctx->getCurrentUser();

        $this->assertNotNull($member);
        $this->assertEquals($resolved->getId(), $member->getId());
        $this->assertEquals($claimed_email, $member->getEmail(), 'The claim email must be applied to the resolved member');

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        $other = $em->find(Member::class, $other_id);
        $this->assertEquals(
            sprintf('%s-invalid@invalid', $other_id),
            $other->getEmail(),
            'The colliding member email must be invalidated'
        );
    }
}