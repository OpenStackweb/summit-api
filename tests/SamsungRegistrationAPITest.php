<?php namespace Tests;
/*
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
use App\Services\Apis\Samsung\SamsungRegistrationAPI;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use models\summit\Summit;

final class SamsungRegistrationAPITest extends TestCase
{
    public function testUserList(){
        $api = new SamsungRegistrationAPI(
            App::make( ClientInterface::class),
            ""
        );
        $summit = new Summit();
        $summit->setExternalRegistrationFeedApiKey("");
        $summit->addRegistrationFeedMetadata("forum","");
        $summit->addRegistrationFeedMetadata("region","");
        $summit->addRegistrationFeedMetadata("gbm","");
        $summit->addRegistrationFeedMetadata("year","");
        $api->userList($summit);
    }
}