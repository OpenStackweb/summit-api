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
        if(!$ctx instanceof IResourceServerContext)
            throw new \Exception();

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
    }
}