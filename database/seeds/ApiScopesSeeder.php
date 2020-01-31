<?php
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;;
use App\Models\ResourceServer\ApiScope;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Support\Facades\DB;
use App\Security\SummitScopes;
use App\Security\OrganizationScopes;
use App\Security\MemberScopes;
/**
 * Class ApiScopesSeeder
 */
final class ApiScopesSeeder extends Seeder
{

    public function run()
    {
        DB::table('endpoint_api_scopes')->delete();
        DB::table('api_scopes')->delete();

        $this->seedSummitScopes();
        $this->seedMembersScopes();
        $this->seedTeamsScopes();
        $this->seedTagsScopes();
        $this->seedCompaniesScopes();
        $this->seedGroupsScopes();
        $this->seedOrganizationScopes();
    }

    private function seedSummitScopes()
    {

        $current_realm = Config::get('app.scope_base_realm');
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'summits']);

        $scopes = [
            [
                'name' => sprintf(SummitScopes::ReadSummitData, $current_realm),
                'short_description' => 'Get Summit Data',
                'description' => 'Grants read only access for Summits Data',
            ],
            [
                'name' => sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                'short_description' => 'Get All Summits Data',
                'description' => 'Grants read only access for All Summits Data',
            ],
            [
                'name' => sprintf('%s/me/read', $current_realm),
                'short_description' => 'Get own summit member data',
                'description' => 'Grants read only access for our own summit member data',
            ],
            [
                'name' => sprintf('%s/me/summits/events/favorites/add', $current_realm),
                'short_description' => 'Allows to add Summit events as favorite',
                'description' => 'Allows to add Summit events as favorite',
            ],
            [
                'name' => sprintf('%s/me/summits/events/favorites/delete', $current_realm),
                'short_description' => 'Allows to remove Summit events as favorite',
                'description' => 'Allows to remove Summit events as favorite',
            ],
            [
                'name' => sprintf(SummitScopes::WriteSummitData, $current_realm),
                'short_description' => 'Write Summit Data',
                'description' => 'Grants write access for Summits Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteRegistrationData, $current_realm),
                'short_description' => 'Write Registration Data',
                'description' => 'Grants write access for Registration Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteEventData, $current_realm),
                'short_description' => 'Write Summit Events',
                'description' => 'Grants write access for Summits Events',
            ],
            [
                'name' => sprintf(SummitScopes::WritePresentationData, $current_realm),
                'short_description' => 'Write Summit Presentations',
                'description' => 'Grants write access for Summits Presentations',
            ],
            array(
                'name' => sprintf('%s/summits/delete-event', $current_realm),
                'short_description' => 'Delete Summit Events',
                'description' => 'Grants delete access for Summits Events',
            ),
            array(
                'name' => sprintf('%s/summits/publish-event', $current_realm),
                'short_description' => 'Publish/UnPublish Summit Events',
                'description' => 'Grants Publish/UnPublish access for Summits Events',
            ),
            [
                'name' => sprintf('%s/summits/read-external-orders', $current_realm),
                'short_description' => 'Allow to read External Orders',
                'description' => 'Allow to read External Orders',
            ],
            [
                'name' => sprintf('%s/summits/confirm-external-orders', $current_realm),
                'short_description' => 'Allow to confirm External Orders',
                'description' => 'Allow to confirm External Orders',
            ],
            [
                'name' => sprintf('%s/summits/write-videos', $current_realm),
                'short_description' => 'Allow to write presentation videos',
                'description' => 'Allow to write presentation videos',
            ],
            [
                'name' => sprintf('%s/summits/read-notifications', $current_realm),
                'short_description' => 'Allow to read summit notifications',
                'description' => 'Allow to read summit notifications',
            ],
            [
                'name' => sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                'short_description' => 'Write Speakers Data',
                'description' => 'Grants write access for Speakers Data',
            ],
            [
                'name' => sprintf(SummitScopes::ReadSpeakersData, $current_realm),
                'short_description' => 'Read Speakers Data',
                'description' => 'Grants read access for Speakers Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                'short_description' => 'Write My Speakers Profile Data',
                'description' => 'Grants write access for My Speaker Profile Data',
            ],
            [
                'name' => sprintf(SummitScopes::ReadMySpeakersData, $current_realm),
                'short_description' => 'Read My Speakers Profile Data',
                'description' => 'Grants read access for My Speaker Profile Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                'short_description' => 'Write Attendees Data',
                'description' => 'Grants write access for Attendees Data',
            ],
            [
                'name' => sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                'short_description' => 'Write Promo Codes Data',
                'description' => 'Grants write access for Promo Codes Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteLocationsData, $current_realm),
                'short_description' => 'Write Summit Locations Data',
                'description' => 'Grants write access for Summit Locations Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteLocationBannersData, $current_realm),
                'short_description' => 'Write Summit Location Banners Data',
                'description' => 'Grants write access for Summit Location Banners Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteTrackTagGroupsData, $current_realm),
                'short_description' => 'Write Summit Track Tag Groups Data',
                'description' => 'Grants write access for Summit Track Tag Groups Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                'short_description' => 'Write Summit Track Question Template Data',
                'description' => 'Grants write access for Summit Track Question Template Data',
            ],
            [
                'name' => sprintf(SummitScopes::WritePresentationVideosData, $current_realm),
                'short_description' => 'Write Summit Presentation Videos Data',
                'description' => 'Grants write access for Summit Presentation Videos Data',
            ],
            [
                'name' => sprintf(SummitScopes::WritePresentationSlidesData, $current_realm),
                'short_description' => 'Write Summit Presentation Slides Data',
                'description' => 'Grants write access for Summit Presentation Slides Data',
            ],
            [
                'name' => sprintf(SummitScopes::WritePresentationLinksData, $current_realm),
                'short_description' => 'Write Summit Presentation Links Data',
                'description' => 'Grants write access for Summit Presentation Links Data',
            ],
            [
                'name' => sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                'short_description' => 'Write Summit Presentation Materials Data',
                'description' => 'Grants write access for Summit Materials Links Data',
            ],
            [
                'name' => sprintf(SummitScopes::ReadMyBookableRoomsReservationData, $current_realm),
                'short_description' => 'Read my bookable rooms reservations',
                'description' => 'Read my bookable rooms reservations',
            ],
            [
                'name' => sprintf(SummitScopes::WriteMyBookableRoomsReservationData, $current_realm),
                'short_description' => 'Write my bookable rooms reservations',
                'description' => 'Write my bookable rooms reservations',
            ],
            [
                'name' => sprintf(SummitScopes::CreateOfflineRegistrationOrders, $current_realm),
                'short_description' => 'Create summit offline registration orders',
                'description' => 'Create summit offline registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::CreateRegistrationOrders, $current_realm),
                'short_description' => 'Create summit registration orders',
                'description' => 'Create summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::DeleteRegistrationOrders, $current_realm),
                'short_description' => 'Delete summit registration orders',
                'description' => 'Delete summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::DeleteMyRegistrationOrders, $current_realm),
                'short_description' => 'Delete my summit registration orders',
                'description' => 'Delete my summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                'short_description' => 'Read my summit registration orders',
                'description' => 'Read my summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                'short_description' => 'Read summit registration orders',
                'description' => 'Read summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                'short_description' => 'Update summit registration orders',
                'description' => 'Update summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                'short_description' => 'Update my summit registration orders',
                'description' => 'Update my summit registration orders',
            ],
            [
                'name' => sprintf(SummitScopes::UpdateRegistrationOrdersBadges, $current_realm),
                'short_description' => 'Update  summit registration orders badges',
                'description' => 'Update summit registration orders badges',
            ],
            [
                'name' => sprintf(SummitScopes::PrintRegistrationOrdersBadges, $current_realm),
                'short_description' => 'print summit registration orders badges',
                'description' => 'print summit registration orders badges',
            ],
            [
                'name' => sprintf(SummitScopes::ReadMyBadgeScan, $current_realm),
                'short_description' => 'read my badge scans',
                'description' => 'read my badge scans',
            ],
            [
                'name' => sprintf(SummitScopes::ReadBadgeScan, $current_realm),
                'short_description' => 'read badge scans',
                'description' => 'read badge scans',
            ],
            [
                'name' => sprintf(SummitScopes::WriteBadgeScan, $current_realm),
                'short_description' => 'write badge scans',
                'description' => 'write badge scans',
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

    private function seedMembersScopes(){
        $current_realm = Config::get('app.scope_base_realm');
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'members']);

        $scopes = [
            [
                'name' => sprintf(MemberScopes::ReadMemberData, $current_realm),
                'short_description' => 'Get Members Data',
                'description' => 'Grants read only access for Members Data',
            ],
            [
                'name' => sprintf(MemberScopes::ReadMyMemberData, $current_realm),
                'short_description' => 'Get My Member Data',
                'description' => 'Grants read only access for My Member',
            ],
            [
                'name' => sprintf(MemberScopes::WriteMemberData, $current_realm),
                'short_description' => 'Allows write only access to members',
                'description' => 'Allows write only access to memberss',
            ],
            [
                'name' => sprintf(MemberScopes::WriteMyMemberData, $current_realm),
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
        $current_realm = Config::get('app.scope_base_realm');
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'organizations']);

        $scopes = [
            [
                'name' => sprintf(SummitScopes::ReadTagsData, $current_realm),
                'short_description' => 'Get Tags Data',
                'description' => 'Grants read only access for Tags Data',
            ],
            [
                'name' => sprintf(SummitScopes::WriteTagsData, $current_realm),
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
        $current_realm = Config::get('app.scope_base_realm');
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'companies']);

        $scopes = [
            [
                'name'              => sprintf(OrganizationScopes::ReadOrganizationData, $current_realm),
                'short_description' => 'Get Organizations Data',
                'description'       => 'Grants read only access for Organization Data',
            ],
            [
                'name'              => sprintf(OrganizationScopes::WriteOrganizationData, $current_realm),
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
        $current_realm = Config::get('app.scope_base_realm');
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'companies']);

        $scopes = [
            [
                'name'              => sprintf('%s/companies/read', $current_realm),
                'short_description' => 'Get Companies Data',
                'description'       => 'Grants read only access for Companies Data',
            ],
            [
                'name'              => sprintf('%s/companies/write', $current_realm),
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

    private function seedGroupsScopes(){
        $current_realm = Config::get('app.scope_base_realm');
        $api           = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'groups']);

        $scopes = [
            array(
                'name'              => sprintf('%s/groups/read', $current_realm),
                'short_description' => 'Get Groups Data',
                'description'       => 'Grants read only access for Groups Data',
            ),
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
        $current_realm = Config::get('app.scope_base_realm');
        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => 'teams']);

        $scopes = [
            array(
                'name' => sprintf('%s/teams/read', $current_realm),
                'short_description' => 'Get Teams Data',
                'description' => 'Grants read only access for Teams Data',
            ),
            array(
                'name' => sprintf('%s/teams/write', $current_realm),
                'short_description' => 'Write Teams Data',
                'description' => 'Grants write access for Teams Data',
            ),
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