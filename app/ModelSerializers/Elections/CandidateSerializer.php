<?php namespace App\ModelSerializers\Elections;
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

use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class CandidateSerializer
 * @package App\ModelSerializers\Elections
 */
class CandidateSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'MemberId' => 'member_id:json_int',
        'ElectionId' => 'election_id:json_int',
        'HasAcceptedNomination' => 'has_accepted_nomination:json_boolean',
        'GoldMember' => 'is_gold_member:json_boolean',
        'RelationshipToOpenstack' => 'relationship_to_openstack:json_string',
        'Experience' => 'experience:json_string',
        'BoardsRole' => 'boards_role:json_string',
        'Bio' => 'bio:json_string',
        'TopPriority' => 'top_priority:json_string',
    ];

    protected static $expand_mappings = [
        'election' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'election_id',
            'getter' => 'getElection',
            'has' => 'hasElection'
        ],
        'member' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'member_id',
            'getter' => 'getMember',
            'has' => 'hasMember'
        ]
    ];
}