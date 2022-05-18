<?php namespace ModelSerializers;
/**
 * Copyright 2022 OpenStack Foundation
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
/**
 * Class PresentationTrackChairScoreSerializer
 * @package ModelSerializers
 */
final class PresentationTrackChairScoreSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'TypeId'         => 'type_id:json_int',
        'PresentationId' => 'presentation_id:json_int',
        'ReviewerId' => 'reviewer_id:json_int'
    ];

    protected static $expand_mappings = [
        'type' => [
            'type'                  => One2ManyExpandSerializer::class,
            'original_attribute'    => 'type_id',
            'getter'                => 'getType',
            'has'                   => 'hasType'
        ],
        'presentation' => [
            'type'                  => One2ManyExpandSerializer::class,
            'original_attribute'    => 'presentation_id',
            'getter'                => 'getPresentation',
            'has'                   => 'hasPresentation'
        ],
        'reviewer' => [
            'type'                  => One2ManyExpandSerializer::class,
            'original_attribute'    => 'reviewer_id',
            'getter'                => 'getReviewer',
            'has'                   => 'hasReviewer'
        ],
    ];
}