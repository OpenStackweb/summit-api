<?php namespace Database\Seeders;
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
use Illuminate\Database\Seeder;
use App\Models\ResourceServer\ApiScope;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\Security\SummitScopes;
use App\Security\OrganizationScopes;
use App\Security\MemberScopes;
use App\Security\CompanyScopes;
use App\Security\SponsoredProjectScope;
use App\Security\GroupsScopes;
use App\Security\TeamScopes;
/**
 * Class ApiScopesSeeder
 */
final class ApiScopesSeeder extends Seeder
{

    public function run()
    {
        $this->seedSummitScopes();
        $this->seedAuditLogScopes();
        $this->seedMembersScopes();
        $this->seedTeamsScopes();
        $this->seedTagsScopes();
        $this->seedCompaniesScopes();
        $this->seedSponsoredProjectsScopes();
        $this->seedGroupsScopes();
        $this->seedOrganizationScopes();
        $this->seedSummitAdminGroupScopes();
        $this->seedSummitMediaFileTypes();
        $this->seedElectionsScopes();
        $this->seedRSVPInvitationScopes();
    }

    private function seedSummitScopes()
    {

        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'summits']);

        $scopes = [
            [
                'name' => SummitScopes::ReadSummitData,
                'short_description' => 'Get Summit Data',
                'description' => 'Grants read only access for Summits Data',
            ],
            [
                'name' => SummitScopes::ReadAllSummitData,
                'short_description' => 'Get All Summits Data',
                'description' => 'Grants read only access for All Summits Data',
            ],
            [
                'name' => SummitScopes::MeRead,
                'short_description' => 'Get own summit member data',
                'description' => 'Grants read only access for our own summit member data',
            ],
            [
                'name' => SummitScopes::AddMyFavorites,
                'short_description' => 'Allows to add Summit events as favorite',
                'description' => 'Allows to add Summit events as favorite',
            ],
            [
                'name' => SummitScopes::DeleteMyFavorites,
                'short_description' => 'Allows to remove Summit events as favorite',
                'description' => 'Allows to remove Summit events as favorite',
            ],
            // enter/leave event
            [
                'name' => SummitScopes::EnterEvent,
                'short_description' => '',
                'description' => '',
            ],
            [
                'name' => SummitScopes::LeaveEvent,
                'short_description' => '',
                'description' => '',
            ],
            [
                'name' => SummitScopes::WriteMetrics,
                'short_description' => '',
                'description' => '',
            ],
            [
                'name' => SummitScopes::ReadMetrics,
                'short_description' => '',
                'description' => '',
            ],
            [
                'name' => SummitScopes::AddMyRSVP,
                'short_description' => 'Allows to add Summit events as RSVP',
                'description' => 'Allows to add Summit events as RSVP',
            ],
            [
                'name' => SummitScopes::DeleteMyRSVP,
                'short_description' => 'Allows to remove Summit events from RSVP',
                'description' => 'Allows to remove Summit events from RSVP',
            ],
            [
                'name' => SummitScopes::AddMySchedule,
                'short_description' => 'Allows to add Summit events to my schedule',
                'description' => 'Allows to add Summit events to my schedule',
            ],
            [
                'name' => SummitScopes::DeleteMySchedule,
                'short_description' => 'Allows to remove Summit events from my schedule',
                'description' => 'Allows to remove Summit events from my schedule',
            ],
            [
                'name' => SummitScopes::AddMyScheduleShareable,
                'short_description' => 'Allows create a shareable link from my schedule',
                'description' => 'Allows create a shareable link from my schedule',
            ],
            [
                'name' => SummitScopes::DeleteMyScheduleShareable,
                'short_description' => 'Allows to delete shareable links from my schedule',
                'description' => 'Allows to delete shareable links from my schedule',
            ],
            [
                'name' => SummitScopes::AddMyEventFeedback,
                'short_description' => 'Allows to create event feedback',
                'description' =>  'Allows to create event feedback',
            ],
            [
                'name' => SummitScopes::DeleteMyEventFeedback,
                'short_description' =>  'Allows to delete event feedback',
                'description' =>  'Allows to delete event feedback',
            ],
            [
                'name' => SummitScopes::SendMyScheduleMail,
                'short_description' => 'Allows to send my schedule share email',
                'description' => 'Allows to send my schedule share email',
            ],
            [
                'name' => SummitScopes::WriteSummitData,
                'short_description' => 'Write Summit Data',
                'description' => 'Grants write access for Summits Data',
            ],
            [
                'name' => SummitScopes::WriteRegistrationData,
                'short_description' => 'Write Registration Data',
                'description' => 'Grants write access for Registration Data',
            ],
            [
                'name' => SummitScopes::WriteEventData,
                'short_description' => 'Write Summit Events',
                'description' => 'Grants write access for Summits Events',
            ],
            [
                'name' => SummitScopes::WritePresentationData,
                'short_description' => 'Write Summit Presentations',
                'description' => 'Grants write access for Summits Presentations',
            ],
            [
                'name' => SummitScopes::DeleteEventData,
                'short_description' => 'Delete Summit Events',
                'description' => 'Grants delete access for Summits Events',
            ],
            [
                'name' => SummitScopes::PublishEventData,
                'short_description' => 'Publish/UnPublish Summit Events',
                'description' => 'Grants Publish/UnPublish access for Summits Events',
            ],
            [
                'name' => SummitScopes::ReadSummitsConfirmExternalOrders,
                'short_description' => 'Allow to read External Orders',
                'description' => 'Allow to read External Orders',
            ],
            [
                'name' => SummitScopes::WriteSummitsConfirmExternalOrders,
                'short_description' => 'Allow to confirm External Orders',
                'description' => 'Allow to confirm External Orders',
            ],
            [
                'name' => SummitScopes::WriteVideoData,
                'short_description' => 'Allow to write presentation videos',
                'description' => 'Allow to write presentation videos',
            ],
            [
                'name' => SummitScopes::ReadNotifications,
                'short_description' => 'Allow to read summit notifications',
                'description' => 'Allow to read summit notifications',
            ],
            [
                'name' => SummitScopes::WriteSpeakersData,
                'short_description' => 'Write Speakers Data',
                'description' => 'Grants write access for Speakers Data',
            ],
            [
                'name' => SummitScopes::ReadSpeakersData,
                'short_description' => 'Read Speakers Data',
                'description' => 'Grants read access for Speakers Data',
            ],
            [
                'name' => SummitScopes::WriteMySpeakersData,
                'short_description' => 'Write My Speakers Profile Data',
                'description' => 'Grants write access for My Speaker Profile Data',
            ],
            [
                'name' => SummitScopes::ReadMySpeakersData,
                'short_description' => 'Read My Speakers Profile Data',
                'description' => 'Grants read access for My Speaker Profile Data',
            ],
            [
                'name' => SummitScopes::WriteAttendeesData,
                'short_description' => 'Write Attendees Data',
                'description' => 'Grants write access for Attendees Data',
            ],
            [
                'name' => SummitScopes::WritePromoCodeData,
                'short_description' => 'Write Promo Codes Data',
                'description' => 'Grants write access for Promo Codes Data',
            ],
            [
                'name' => SummitScopes::WriteLocationsData,
                'short_description' => 'Write Summit Locations Data',
                'description' => 'Grants write access for Summit Locations Data',
            ],
            [
                'name' => SummitScopes::WriteLocationBannersData,
                'short_description' => 'Write Summit Location Banners Data',
                'description' => 'Grants write access for Summit Location Banners Data',
            ],
            [
                'name' => SummitScopes::WriteTrackTagGroupsData,
                'short_description' => 'Write Summit Track Tag Groups Data',
                'description' => 'Grants write access for Summit Track Tag Groups Data',
            ],
            [
                'name' => SummitScopes::WriteTrackQuestionTemplateData,
                'short_description' => 'Write Summit Track Question Template Data',
                'description' => 'Grants write access for Summit Track Question Template Data',
            ],
            [
                'name' => SummitScopes::WritePresentationVideosData,
                'short_description' => 'Write Summit Presentation Videos Data',
                'description' => 'Grants write access for Summit Presentation Videos Data',
            ],
            [
                'name' => SummitScopes::WritePresentationSlidesData,
                'short_description' => 'Write Summit Presentation Slides Data',
                'description' => 'Grants write access for Summit Presentation Slides Data',
            ],
            [
                'name' => SummitScopes::WritePresentationLinksData,
                'short_description' => 'Write Summit Presentation Links Data',
                'description' => 'Grants write access for Summit Presentation Links Data',
            ],
            [
                'name' => SummitScopes::WritePresentationMaterialsData,
                'short_description' => 'Write Summit Presentation Materials Data',
                'description' => 'Grants write access for Summit Materials Links Data',
            ],
            [
                'name' => SummitScopes::ReadMyBookableRoomsReservationData,
                'short_description' => 'Read my bookable rooms reservations',
                'description' => 'Read my bookable rooms reservations',
            ],
            [
                'name' => SummitScopes::WriteMyBookableRoomsReservationData,
                'short_description' => 'Write my bookable rooms reservations',
                'description' => 'Write my bookable rooms reservations',
            ],
            [
                'name' => SummitScopes::CreateOfflineRegistrationOrders,
                'short_description' => 'Create summit offline registration orders',
                'description' => 'Create summit offline registration orders',
            ],
            [
                'name' => SummitScopes::CreateRegistrationOrders,
                'short_description' => 'Create summit registration orders',
                'description' => 'Create summit registration orders',
            ],
            [
                'name' => SummitScopes::DeleteRegistrationOrders,
                'short_description' => 'Delete summit registration orders',
                'description' => 'Delete summit registration orders',
            ],
            [
                'name' => SummitScopes::DeleteMyRegistrationOrders,
                'short_description' => 'Delete my summit registration orders',
                'description' => 'Delete my summit registration orders',
            ],
            [
                'name' => SummitScopes::ReadMyRegistrationOrders,
                'short_description' => 'Read my summit registration orders',
                'description' => 'Read my summit registration orders',
            ],
            [
                'name' => SummitScopes::ReadRegistrationOrders,
                'short_description' => 'Read summit registration orders',
                'description' => 'Read summit registration orders',
            ],
            [
                'name' => SummitScopes::UpdateRegistrationOrders,
                'short_description' => 'Update summit registration orders',
                'description' => 'Update summit registration orders',
            ],
            [
                'name' => SummitScopes::UpdateMyRegistrationOrders,
                'short_description' => 'Update my summit registration orders',
                'description' => 'Update my summit registration orders',
            ],
            [
                'name' => SummitScopes::UpdateRegistrationOrdersBadges,
                'short_description' => 'Update  summit registration orders badges',
                'description' => 'Update summit registration orders badges',
            ],
            [
                'name' => SummitScopes::PrintRegistrationOrdersBadges,
                'short_description' => 'print summit registration orders badges',
                'description' => 'print summit registration orders badges',
            ],
            [
                'name' => SummitScopes::ReadMyBadgeScan,
                'short_description' => 'read my badge scans',
                'description' => 'read my badge scans',
            ],
            [
                'name' => SummitScopes::ReadBadgeScanValidate,
                'short_description' => 'validate badge scan',
                'description' => 'validate badge scan',
            ],
            [
                'name' => SummitScopes::WriteMyBadgeScan,
                'short_description' => 'allow to share my badge with sponsors',
                'description' => 'allow to share my badge with sponsors',
            ],
            [
                'name' => SummitScopes::ReadBadgeScan,
                'short_description' => 'read badge scans',
                'description' => 'read badge scans',
            ],
            [
                'name' => SummitScopes::WriteBadgeScan,
                'short_description' => 'write badge scans',
                'description' => 'write badge scans',
            ],
            [
                'name' => SummitScopes::ReadPaymentProfiles,
                'short_description' => 'read summit payment profiles',
                'description' => 'read summit payment profiles',
            ],
            [
                'name' => SummitScopes::WritePaymentProfiles,
                'short_description' => 'write summit payment profiles',
                'description' => 'write summit payment profiles',
            ],
            [
                'name' => SummitScopes::WriteRegistrationInvitations,
                'short_description' => 'write summit registration invitation',
                'description' => 'write summit registration invitation',
            ],
            [
                'name' => SummitScopes::ReadRegistrationInvitations,
                'short_description' => 'read summit registration invitation',
                'description' => 'read summit registration invitation',
            ],
            [
                'name' => SummitScopes::ReadMyRegistrationInvitations,
                'short_description' => 'read my summit registration invitation',
                'description' => 'read my summit registration invitation',
            ],
            [
                'name' => SummitScopes::DoVirtualCheckIn,
                'short_description' => 'Allow virtual Check In',
                'description' => 'Allow virtual Check In',
            ],
            [
                'name' => SummitScopes::Allow2PresentationAttendeeVote,
                'short_description' => 'Allow Attendee Vote on Presentation',
                'description' => 'Allow Attendee Vote on Presentation',
            ],
            [
                'name' => SummitScopes::ReadAttendeeNotesData,
                'short_description' => 'Read Attendee Notes Data',
                'description' => 'Grants read access for Attendee Notes Data',
            ],
            [
                'name' => SummitScopes::WriteAttendeeNotesData,
                'short_description' => 'Write Attendee Notes Data',
                'description' => 'Grants write access for Attendee Notes Data',
            ]
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();

    }

    private function seedAuditLogScopes()
    {
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'audit-logs']);

        $scopes = [
            [
                'name' => SummitScopes::ReadAuditLogs,
                'short_description' => 'Get Audit Logs Data',
                'description' => 'Grants read only access for Audit Logs Data',
            ]
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedMembersScopes(){
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'members']);

        $scopes = [
            [
                'name' => MemberScopes::ReadMemberData,
                'short_description' => 'Get Members Data',
                'description' => 'Grants read only access for Members Data',
            ],
            [
                'name' => MemberScopes::ReadMyMemberData,
                'short_description' => 'Get My Member Data',
                'description' => 'Grants read only access for My Member',
            ],
            [
                'name' => MemberScopes::WriteMemberData,
                'short_description' => 'Allows write only access to members',
                'description' => 'Allows write only access to memberss',
            ],
            [
                'name' => MemberScopes::WriteMyMemberData,
                'short_description' => 'Allows write only access to my Member Data',
                'description' =>  'Allows write only access to my Member Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedTagsScopes(){
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'organizations']);

        $scopes = [
            [
                'name' => SummitScopes::ReadTagsData,
                'short_description' => 'Get Tags Data',
                'description' => 'Grants read only access for Tags Data',
            ],
            [
                'name' => SummitScopes::WriteTagsData,
                'short_description' => 'Write Tags Data',
                'description' => 'Grants write access to Tags Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedOrganizationScopes(){

        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'companies']);

        $scopes = [
            [
                'name'              => OrganizationScopes::ReadOrganizationData,
                'short_description' => 'Get Organizations Data',
                'description'       => 'Grants read only access for Organization Data',
            ],
            [
                'name'              => OrganizationScopes::WriteOrganizationData,
                'short_description' => 'Write Companies Data',
                'description'       => 'Grants write access for Organization Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedCompaniesScopes(){
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'companies']);

        $scopes = [
            [
                'name'              => CompanyScopes::Read,
                'short_description' => 'Get Companies Data',
                'description'       => 'Grants read only access for Companies Data',
            ],
            [
                'name'              => CompanyScopes::Write,
                'short_description' => 'Write Companies Data',
                'description'       => 'Grants write only access for Companies Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedSponsoredProjectsScopes(){
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'sponsored-projects']);

        $scopes = [
            [
                'name'              => SponsoredProjectScope::Read,
                'short_description' => 'Get Sponsored Projects Data',
                'description'       => 'Grants read only access for Sponsored Projects Data',
            ],
            [
                'name'              => SponsoredProjectScope::Write,
                'short_description' => 'Write Sponsored Projects Data',
                'description'       => 'Grants write only access for Sponsored Projects Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedGroupsScopes(){
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'groups']);

        $scopes = [
            [
                'name'              => GroupsScopes::ReadData,
                'short_description' => 'Get Groups Data',
                'description'       => 'Grants read only access for Groups Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedTeamsScopes(){
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'teams']);

        $scopes = [
            [
                'name' => TeamScopes::Read,
                'short_description' => 'Get Teams Data',
                'description' => 'Grants read only access for Teams Data',
            ],
            [
                'name' => TeamScopes::Write,
                'short_description' => 'Write Teams Data',
                'description' => 'Grants write access for Teams Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedSummitAdminGroupScopes(){


        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'summit-administrator-groups']);

        $scopes = [
            [
                'name' => SummitScopes::ReadSummitAdminGroups,
                'short_description' => 'Get Summit Admin Groups Data',
                'description' => 'Grants read only access for Summit Admin Groups Data',
            ],
            [
                'name' => SummitScopes::WriteSummitAdminGroups,
                'short_description' => 'Write Summit Admin Groups Data',
                'description' => 'Grants write access to Summit Admin Groups Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedSummitMediaFileTypes(){

        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'summit-media-file-types']);

        $scopes = [
            [
                'name' => SummitScopes::ReadSummitMediaFileTypes,
                'short_description' => 'Get Summit Media File Types Data',
                'description' => 'Grants read only access for Summit Media File Types Data',
            ],
            [
                'name' => SummitScopes::WriteSummitMediaFileTypes,
                'short_description' => 'Write Summit Media File Types Data',
                'description' => 'Grants write access to Summit Media File Types Data',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    private function seedElectionsScopes(){

        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'elections']);

        $scopes = [
            [
                'name' => ElectionScopes::ReadAllElections,
                'short_description' => 'Read All Election Data',
                'description' => 'Read All Election Data',
            ],
            [
                'name' => ElectionScopes::WriteMyCandidateProfile,
                'short_description' => 'Writes my candidate profile',
                'description' => 'Writes my candidate profile',
            ],
            [
                'name' => ElectionScopes::NominatesCandidates,
                'short_description' => 'Allows to nominate candidates on current election',
                'description' => 'Allows to nominate candidates on current election',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();
    }

    public function seedRSVPInvitationScopes():void{
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'rsvp-invitations']);

        $scopes = [
            [
                'name' => RSVPInvitationsScopes::Read,
                'short_description' => 'Read RSVP Invitations Data',
                'description' => 'Read RSVP Invitations Data',
            ],
            [
                'name' => RSVPInvitationsScopes::Write,
                'short_description' => 'Write RSVP Invitations Data',
                'description' => 'Write RSVP Invitations Data',
            ],
            [
                'name' => RSVPInvitationsScopes::Send,
                'short_description' => 'Send RSVP Invitations',
                'description' => 'Send RSVP Invitations',
            ],
        ];

        foreach ($scopes as $scope_info) {
            $scope = new ApiScope();
            $scope->setName($scope_info['name']);
            $scope->setShortDescription($scope_info['short_description']);
            $scope->setDescription($scope_info['description']);
            $scope->setActive(true);
            $scope->setDefault(false);
            $scope->setApi($api);
            EntityManager::persist($scope);
        }

        EntityManager::flush();

    }

}
