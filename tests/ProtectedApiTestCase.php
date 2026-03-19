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
use App\Security\RSVPInvitationsScopes;
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
use App\Security\TeamScopes;
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

    private $user_email;

    private $user_first_name;

    private $user_last_name;

    /**
     * @param string|null $user_email
     */
    public function setUserEmail(?string $user_email): void
    {
        $this->user_email = $user_email;
    }

    /**
     * @param string|null $user_first_name
     */
    public function setUserFirstName(?string $user_first_name): void
    {
        $this->user_first_name = $user_first_name;
    }

    /**
     * @param string|null $user_last_name
     */
    public function setUserLastName(?string $user_last_name): void
    {
        $this->user_last_name = $user_last_name;
    }
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
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
            SummitScopes::WriteSummitData,
            SummitScopes::WriteEventData,
            SummitScopes::PublishEventData,
            SummitScopes::DeleteEventData,
            SummitScopes::ReadSummitsConfirmExternalOrders,
            SummitScopes::WriteSummitsConfirmExternalOrders,
            SummitScopes::WriteVideoData,
            SummitScopes::MeRead,
            SummitScopes::ReadNotifications,
            MemberScopes::ReadMemberData,
            MemberScopes::ReadMyMemberData,
            $url . '/members/invitations/read',
            $url . '/members/invitations/write',
            TeamScopes::Read,
            TeamScopes::Write,
            SummitScopes::AddMyFavorites,
            SummitScopes::DeleteMyFavorites,
            SummitScopes::WriteSpeakersData,
            SummitScopes::WriteMySpeakersData,
            SummitScopes::WriteAttendeesData,
            MemberScopes::WriteMemberData,
            MemberScopes::WriteMyMemberData,
            SummitScopes::WritePromoCodeData,
            OrganizationScopes::WriteOrganizationData,
            OrganizationScopes::ReadOrganizationData,
            SummitScopes::WritePresentationMaterialsData,
            SummitScopes::ReadMyBookableRoomsReservationData,
            SummitScopes::WriteMyBookableRoomsReservationData,
            SummitScopes::SendMyScheduleMail,
            SummitScopes::ReadMyRegistrationOrders,
            SummitScopes::ReadRegistrationOrders,
            SummitScopes::UpdateRegistrationOrders,
            SummitScopes::CreateOfflineRegistrationOrders,
            SummitScopes::DeleteRegistrationOrders,
            SummitScopes::UpdateRegistrationOrders,
            SummitScopes::UpdateMyRegistrationOrders,
            SummitScopes::WriteBadgeScan,
            SummitScopes::ReadBadgeScan,
            SummitScopes::CreateRegistrationOrders,
            SummitScopes::ReadSummitAdminGroups,
            SummitScopes::WriteSummitAdminGroups,
            SummitScopes::EnterEvent,
            SummitScopes::LeaveEvent,
            SummitScopes::ReadSummitMediaFileTypes,
            SummitScopes::WriteSummitMediaFileTypes,
            CompanyScopes::Write,
            CompanyScopes::Read,
            SponsoredProjectScope::Write,
            SponsoredProjectScope::Read,
            SummitScopes::WriteMetrics,
            SummitScopes::ReadMetrics,
            SummitScopes::Allow2PresentationAttendeeVote,
            ElectionScopes::ReadAllElections,
            ElectionScopes::NominatesCandidates,
            ElectionScopes::WriteMyCandidateProfile,
            SummitScopes::ReadAuditLogs,
            SummitScopes::ReadMyBadgeScan,
            SummitScopes::ReadBadgeScanValidate,
            RSVPInvitationsScopes::Write,
            RSVPInvitationsScopes::Send,
            RSVPInvitationsScopes::Read,
        );

        return AccessToken::createFromParams(
            [
                'access_token'        => '123456789',
                'scope'               => implode(' ', $scopes),
                'client_id'           => '1',
                'audience'            => $realm,
                'user_id'             => $this->user_id,
                'user_external_id'    => $this->user_external_id,
                'user_email'          => $this->user_email,
                'user_email_verified' => true,
                'user_first_name'     => $this->user_first_name,
                'user_last_name'      => $this->user_last_name,
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
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData,
            SummitScopes::WriteSummitData,
            SummitScopes::WriteEventData,
            SummitScopes::PublishEventData,
            SummitScopes::DeleteEventData,
            SummitScopes::ReadSummitsConfirmExternalOrders,
            SummitScopes::WriteSummitsConfirmExternalOrders,
            SummitScopes::WriteVideoData,
            SummitScopes::MeRead,
            SummitScopes::ReadNotifications,
            MemberScopes::ReadMemberData,
            MemberScopes::ReadMyMemberData,
            $url . '/members/invitations/read',
            $url . '/members/invitations/write',
            TeamScopes::Read,
            TeamScopes::Write,
            SummitScopes::AddMyFavorites,
            SummitScopes::DeleteMyFavorites,
            SummitScopes::WriteSpeakersData,
            SummitScopes::WriteMySpeakersData,
            SummitScopes::WriteAttendeesData,
            MemberScopes::WriteMemberData,
            SummitScopes::WritePromoCodeData,
            OrganizationScopes::WriteOrganizationData,
            OrganizationScopes::ReadOrganizationData,
            SummitScopes::WritePresentationMaterialsData,
            SummitScopes::ReadMyBookableRoomsReservationData,
            SummitScopes::ReadRegistrationOrders,
            SummitScopes::WriteMyBookableRoomsReservationData,
            SummitScopes::ReadMyRegistrationOrders,
            SummitScopes::UpdateRegistrationOrders,
            SummitScopes::UpdateMyRegistrationOrders,
            SummitScopes::CreateOfflineRegistrationOrders,
            SummitScopes::DeleteRegistrationOrders,
            SummitScopes::UpdateRegistrationOrders,
            SummitScopes::WriteBadgeScan,
            SummitScopes::ReadBadgeScan,
            SummitScopes::CreateRegistrationOrders,
            SummitScopes::ReadSummitAdminGroups,
            SummitScopes::WriteSummitAdminGroups,
            SummitScopes::EnterEvent,
            SummitScopes::LeaveEvent,
            SummitScopes::ReadSummitMediaFileTypes,
            SummitScopes::WriteSummitMediaFileTypes,
            SummitScopes::WriteMetrics,
            SummitScopes::ReadMetrics,
            CompanyScopes::Write,
            CompanyScopes::Read,
            SponsoredProjectScope::Write,
            SponsoredProjectScope::Read,
            SummitScopes::ReadBadgeScanValidate,
            ElectionScopes::ReadAllElections,
            ElectionScopes::NominatesCandidates,
            ElectionScopes::WriteMyCandidateProfile,
            SummitScopes::Allow2PresentationAttendeeVote,
            RSVPInvitationsScopes::Write,
            RSVPInvitationsScopes::Send,
            RSVPInvitationsScopes::Read,
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
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());
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
