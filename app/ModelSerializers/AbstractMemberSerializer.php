<?php namespace ModelSerializers;
/**
 * Copyright 2017 OpenStack Foundation
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\main\Member;

/**
 * Class AbstractMemberSerializer
 * @package ModelSerializers
 */
class AbstractMemberSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'FirstName'       => 'first_name:json_string',
        'LastName'        => 'last_name:json_string',
        'Gender'          => 'gender:json_string',
        'GitHubUser'      => 'github_user:json_string',
        'Bio'             => 'bio:json_string',
        'LinkedInProfile' => 'linked_in:json_string',
        'IrcHandle'       => 'irc:json_string',
        'TwitterHandle'   => 'twitter:json_string',
        'State'           => 'state:json_string',
        'Country'         => 'country:json_string',
        'Active'          => 'active:json_boolean',
        'EmailVerified'   => 'email_verified:json_boolean',
        'ProfilePhotoUrl' => 'pic:json_url',
        'MembershipType' => 'membership_type:json_string',
        'LatestCandidateProfileId' => 'candidate_profile_id:json_int',
    ];

    protected static $allowed_relations = [
        'groups',
        'affiliations',
        'all_affiliations',
        'ccla_teams',
        'election_applications',
        'candidate_profile',
        'election_nominations',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $member  = $this->object;
        if(!$member instanceof Member) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('groups', $relations) && !isset($values['groups']))
            $values['groups'] = $member->getGroupsIds();

        if(in_array('ccla_teams', $relations) && !isset($values['ccla_teams']))
            $values['ccla_teams'] = $member->getCCLATeamsIds();

        if(in_array('affiliations', $relations) && !isset($values['affiliations'])){
            $res = [];
            foreach ($member->getCurrentAffiliations() as $affiliation){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($affiliation)
                    ->serialize('organization');
            }
            $values['affiliations'] = $res;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'groups' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getGroups',
        ],
        'ccla_teams' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getCCLATeams',
        ],
        'all_affiliations' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllAffiliations',
        ],
        'candidate_profile' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'candidate_profile_id',
            'getter' => 'getLatestCandidateProfile',
            'has' => 'hasLatestCandidateProfile'
        ],
        'election_applications' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getLatestElectionApplications',
        ],
        'election_nominations' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getLatestElectionNominations',
        ],
    ];
}