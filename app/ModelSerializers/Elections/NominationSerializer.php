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
 * Class NominationSerializer
 * @package App\ModelSerializers\Elections
 */
class NominationSerializer  extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'CandidateId' => 'candidate_id:json_int',
        'NominatorId' => 'nominator_id:json_int',
        'ElectionId' => 'election_id:json_int',
    ];

    protected static $expand_mappings = [
        'election' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'election_id',
            'getter' => 'getElection',
            'has' => 'hasElection'
        ],
        'candidate' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'candidate_id',
            'getter' => 'getCandidate',
            'has' => 'hasCandidate'
        ],
        'nominator' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'nominator_id',
            'getter' => 'getNominator',
            'has' => 'hasNominator'
        ],
    ];
}