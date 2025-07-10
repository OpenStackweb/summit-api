<?php namespace Tests;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Security\ElectionScopes;
use App\Services\Apis\AddressInfo;
use App\Services\Apis\GeoCoordinatesInfo;
use App\Services\Apis\IGeoCodingAPI;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use models\oauth2\AccessToken;
use App\Models\ResourceServer\IAccessTokenService;
use App\Security\SummitScopes;
use App\Security\OrganizationScopes;
use App\Security\MemberScopes;
use App\Models\Foundation\Main\IGroup;
use App\Security\CompanyScopes;
use App\Security\SponsoredProjectScope;
use Mockery;
/**
 * Class AccessTokenServiceStub
 */
class AccessTokenServiceStub implements IAccessTokenService
{
    private $idp_user_groups = [];
    public function __construct(array $idp_user_groups = [
        [
            'slug' => 'badge-printers',

        ],
        [
            'slug' => 'administrators',

        ],
    ])
    {
        $this->idp_user_groups = $idp_user_groups;
    }
    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @param mixed $user_external_id
     */
    public function setUserExternalId($user_external_id): void
    {
        $this->user_external_id = $user_external_id;
    }

    private $user_id;

    private $user_external_id;
    /**
     * @param string $token_value
     * @return AccessToken
     * @throws \libs\oauth2\OAuth2InvalidIntrospectionResponse
     */
    public function get($token_value)
    {
        $url   = Config::get('app.scope_base_realm');
        $parts = @parse_url($url);
        $realm = $parts['host'];

        $scopes = array(
            $url . '/public-clouds/read',
            $url . '/private-clouds/read',
            $url . '/consultants/read',
            $url . '/summits/read',
            $url . '/summits/read/all',
            $url . '/summits/write',
            $url . '/summits/write-event',
            $url . '/summits/publish-event',
            $url . '/summits/delete-event',
            $url . '/summits/read-external-orders',
            $url . '/summits/confirm-external-orders',
            $url . '/summits/write-videos',
            $url . '/me/read',
            $url . '/summits/read-notifications',
            $url . '/members/read',
            $url . '/members/read/me',
            $url . '/members/invitations/read',
            $url . '/members/invitations/write',
            $url . '/teams/read',
            $url . '/teams/write',
            $url . '/me/summits/events/favorites/add',
            $url . '/me/summits/events/favorites/delete',
            sprintf(SummitScopes::WriteSpeakersData, $url),
            sprintf(SummitScopes::WriteMySpeakersData, $url),
            sprintf(SummitScopes::WriteAttendeesData, $url),
            sprintf(MemberScopes::WriteMemberData, $url),
            sprintf(MemberScopes::WriteMyMemberData, $url),
            sprintf(SummitScopes::WritePromoCodeData, $url),
            sprintf(OrganizationScopes::WriteOrganizationData, $url),
            sprintf(OrganizationScopes::ReadOrganizationData, $url),
            sprintf(SummitScopes::WritePresentationMaterialsData, $url),
            sprintf(SummitScopes::ReadMyBookableRoomsReservationData, $url),
            sprintf(SummitScopes::WriteMyBookableRoomsReservationData, $url),
            sprintf(SummitScopes::SendMyScheduleMail, $url),
            sprintf(SummitScopes::ReadMyRegistrationOrders, $url),
            sprintf(SummitScopes::ReadRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateRegistrationOrders, $url),
            sprintf(SummitScopes::CreateOfflineRegistrationOrders, $url),
            sprintf(SummitScopes::DeleteRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateMyRegistrationOrders, $url),
            sprintf(SummitScopes::WriteBadgeScan, $url),
            sprintf(SummitScopes::ReadBadgeScan, $url),
            sprintf(SummitScopes::CreateRegistrationOrders, $url),
            sprintf(SummitScopes::ReadSummitAdminGroups, $url),
            sprintf(SummitScopes::WriteSummitAdminGroups, $url),
            sprintf(SummitScopes::EnterEvent, $url),
            sprintf(SummitScopes::LeaveEvent, $url),
            sprintf(SummitScopes::ReadSummitMediaFileTypes, $url),
            sprintf(SummitScopes::WriteSummitMediaFileTypes, $url),
            sprintf(CompanyScopes::Write, $url),
            sprintf(CompanyScopes::Read, $url),
            sprintf(SponsoredProjectScope::Write, $url),
            sprintf(SponsoredProjectScope::Read, $url),
            sprintf(SummitScopes::WriteMetrics, $url),
            sprintf(SummitScopes::ReadMetrics, $url),
            sprintf(SummitScopes::Allow2PresentationAttendeeVote, $url),
            ElectionScopes::NominatesCandidates,
            ElectionScopes::WriteMyCandidateProfile,
            sprintf(SummitScopes::ReadAuditLogs, $url),
            sprintf(SummitScopes::ReadMyBadgeScan, $url),
        );

        return AccessToken::createFromParams(
            [
                'access_token'        => '123456789',
                'scope'               => implode(' ', $scopes),
                'client_id'           => '1',
                'audience'            => $realm,
                'user_id'             => $this->user_id,
                'user_external_id'    => $this->user_external_id,
                'expires_in'          => 3600,
                'application_type'    => 'WEB_APPLICATION',
                'allowed_return_uris' => 'https://www.openstack.org/OpenStackIdAuthenticator,https://www.openstack.org/Security/login',
                'allowed_origins'     =>  '',
                'user_groups' => $this->idp_user_groups,
            ]
        );
    }
}

class AccessTokenServiceStub2 implements IAccessTokenService
{

    /**
     * @param string $token_value
     * @return AccessToken
     * @throws \libs\oauth2\OAuth2InvalidIntrospectionResponse
     */
    public function get($token_value)
    {
        $url = Config::get('app.scope_base_realm');
        $parts = @parse_url($url);
        $realm = $parts['host'];

        $scopes = array(
            $url . '/public-clouds/read',
            $url . '/private-clouds/read',
            $url . '/consultants/read',
            $url . '/summits/read',
            $url . '/summits/read/all',
            $url . '/summits/write',
            $url . '/summits/write-event',
            $url . '/summits/publish-event',
            $url . '/summits/delete-event',
            $url . '/summits/read-external-orders',
            $url . '/summits/confirm-external-orders',
            $url . '/summits/write-videos',
            $url . '/summits/write-videos',
            $url . '/me/read',
            $url . '/summits/read-notifications',
            $url . '/members/read',
            $url . '/members/read/me',
            $url . '/members/invitations/read',
            $url . '/members/invitations/write',
            $url . '/teams/read',
            $url . '/teams/write',
            $url . '/me/summits/events/favorites/add',
            $url . '/me/summits/events/favorites/delete',
            sprintf(SummitScopes::WriteSpeakersData, $url),
            sprintf(SummitScopes::WriteMySpeakersData, $url),
            sprintf(SummitScopes::WriteAttendeesData, $url),
            sprintf(MemberScopes::WriteMemberData, $url),
            sprintf(SummitScopes::WritePromoCodeData, $url),
            sprintf(OrganizationScopes::WriteOrganizationData, $url),
            sprintf(OrganizationScopes::ReadOrganizationData, $url),
            sprintf(SummitScopes::WritePresentationMaterialsData, $url),
            sprintf(SummitScopes::ReadMyBookableRoomsReservationData, $url),
            sprintf(SummitScopes::ReadRegistrationOrders, $url),
            sprintf(SummitScopes::WriteMyBookableRoomsReservationData, $url),
            sprintf(SummitScopes::ReadMyRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateMyRegistrationOrders, $url),
            sprintf(SummitScopes::CreateOfflineRegistrationOrders, $url),
            sprintf(SummitScopes::DeleteRegistrationOrders, $url),
            sprintf(SummitScopes::UpdateRegistrationOrders, $url),
            sprintf(SummitScopes::WriteBadgeScan, $url),
            sprintf(SummitScopes::ReadBadgeScan, $url),
            sprintf(SummitScopes::CreateRegistrationOrders, $url),
            sprintf(SummitScopes::ReadSummitAdminGroups, $url),
            sprintf(SummitScopes::WriteSummitAdminGroups, $url),
            sprintf(SummitScopes::EnterEvent, $url),
            sprintf(SummitScopes::LeaveEvent, $url),
            sprintf(SummitScopes::ReadSummitMediaFileTypes, $url),
            sprintf(SummitScopes::WriteSummitMediaFileTypes, $url),
            sprintf(SummitScopes::WriteMetrics, $url),
            sprintf(SummitScopes::ReadMetrics, $url),
            sprintf(CompanyScopes::Write, $url),
            sprintf(CompanyScopes::Read, $url),
            sprintf(SponsoredProjectScope::Write, $url),
            sprintf(SponsoredProjectScope::Read, $url),
            ElectionScopes::NominatesCandidates,
            ElectionScopes::WriteMyCandidateProfile,
            sprintf(SummitScopes::Allow2PresentationAttendeeVote, $url),
        );

        return AccessToken::createFromParams(
            [
                'access_token'        => '123456789',
                'scope'               => implode(' ', $scopes),
                'client_id'           => '1',
                'audience'            => $realm,
                'user_id'             =>  null,
                'user_external_id'    => null,
                'expires_in'          => 3600,
                'application_type'    => 'SERVICE',
                'allowed_return_uris' => '',
                'allowed_origins'     =>  '',
                'user_groups' => [
                    [
                    ]
                ],
            ]
        );
    }
}


/**
 * Class ProtectedApiTestCase
 */
abstract class ProtectedApiTestCase extends \Tests\BrowserKitTestCase
{
    use InsertMemberTestData;

    protected $app;
    /**
     * @var string
     */
    protected $access_token;

    protected $current_group = IGroup::Administrators;

    static $service;
    /**
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->app = parent::createApplication();
        self::$service = new AccessTokenServiceStub();

        App::singleton(IAccessTokenService::class, function () { return self::$service; });

        $geoCodingApiMock = Mockery::mock(IGeoCodingAPI::class);

        $geoCodingApiMock->shouldReceive('getAddressInfo')->andReturn
        (
            new AddressInfo
            (
                "ADDRESS",
                "ADDRESS 1",
                "1234",
                "STATE",
                "CITY",
                "USA")
        )->zeroOrMoreTimes();

        $geoCodingApiMock->shouldReceive('getGeoCoordinates')->andReturn
        (
            new GeoCoordinatesInfo
            (
                "99.99",
                "99.99"
            )
        )->zeroOrMoreTimes();
        // replace implementation with mock on IOC containter
        App::singleton(IGeoCodingAPI::class, function() use ($geoCodingApiMock){
            return $geoCodingApiMock;
        });

        return $this->app;
    }

    protected function setCurrentGroup(string $group){
        $this->current_group = $group;
    }

    protected function setUp():void
    {
        $this->access_token = 'TEST_ACCESS_TOKEN';
        parent::setUp();
        self::insertMemberTestData($this->current_group);
        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        parent::tearDown();
    }

    protected function getAuthHeaders():array{
        return [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];
    }
}