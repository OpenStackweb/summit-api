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
use models\oauth2\IResourceServerContext;
/**
 * Class ResourceServerContextTest
 * @package Tests
 */
class ResourceServerContextTest extends BrowserKitTestCase
{
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
}