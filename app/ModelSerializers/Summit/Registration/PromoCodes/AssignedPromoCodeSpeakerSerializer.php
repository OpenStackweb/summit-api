<?php namespace ModelSerializers;
/**
 * Copyright 2023 OpenStack Foundation
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

use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class AssignedPromoCodeSpeakerSerializer
 * @package ModelSerializers
 */
class AssignedPromoCodeSpeakerSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'SpeakerId'  => 'speaker_id:integer',
        'RedeemedAt' => 'redeemed:datetime_epoch',
        'SentAt'     => 'sent:datetime_epoch',
    ];

    protected static $expand_mappings = [
        'speaker' => [
            'type' => One2ManyExpandSerializer::class,
            'getter' => 'getSpeaker',
            'has' => 'hasSpeaker',
            'serializer_type' => SerializerRegistry::SerializerType_Private
        ],
    ];
}