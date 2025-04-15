<?php namespace Tests;
/*
 * Copyright 2025 OpenStack Foundation
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
use App\Services\Apis\ExternalUserApi;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\App;
use Mockery;
use models\summit\ISpeakerRegistrationRequestRepository;
use models\summit\SpeakerRegistrationRequest;

class MemberServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    private $externalUserApiMock;

    private $speaker_registration_request_repository_mock;
    protected function setUp(): void
    {
        parent::setUp();
        $this->externalUserApiMock = Mockery::mock(IExternalUserApi::class);
        App::singleton(
            IExternalUserApi::class,
            function(){
                return $this->externalUserApiMock;
            }
        );

        $speaker_registration_request_mock = Mockery::mock(SpeakerRegistrationRequest::class);
        $speaker_registration_request_mock->shouldReceive('hasSpeaker')->andReturn(true);
        $speaker_registration_request_mock->shouldReceive('getSpeaker')->andReturn(null);

        $this->speaker_registration_request_repository_mock = Mockery::mock(ISpeakerRegistrationRequestRepository::class);
        $this->speaker_registration_request_repository_mock->shouldReceive('getByEmail')->withAnyArgs()->andReturn(
            $speaker_registration_request_mock
        );

        App::singleton(
            ISpeakerRegistrationRequestRepository::class,
            function(){
                return $this->speaker_registration_request_repository_mock;
            }
        );

        self::insertMemberTestData(IGroup::TrackChairs);
        $this->externalUserApiMock->shouldReceive("getUserById")->withAnyArgs()->andReturn([
            'id' => self::$member->getUserExternalId(),
            'email' => self::$member->getEmail(),
            'first_name' => self::$member->getFirstName(),
            'last_name' => self::$member->getLastName(),
            'active' => true,
            'email_verified' => true,
            'bio' => 'test bio',
            'public_profile_show_photo' => true,
            'public_profile_show_fullname' => true,
            'public_profile_show_email' => true,
            'public_profile_show_telephone_number' => true,
            'public_profile_show_bio' => true,
            'public_profile_show_social_media_info' => true,
            'public_profile_allow_chat_with_me' => true,
            'groups' => [],
        ]);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        Mockery::close();
        parent::tearDown();
    }

    public function testRegisterExternalUserById():void{
        $service = App::make(IMemberService::class);
        $service->registerExternalUserById(self::$member->getUserExternalId());
    }
}