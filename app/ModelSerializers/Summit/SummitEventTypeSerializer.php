<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use models\summit\SummitEventType;
/**
 * Class SummitEventTypeSerializer
 * @package ModelSerializers
 */
class SummitEventTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Type'                             => 'name:json_string',
        'ClassName'                        => 'class_name:json_string',
        'Color'                            => 'color:json_color',
        'BlackoutTimes'                    => 'black_out_times:json_string',
        'UseSponsors'                      => 'use_sponsors:json_boolean',
        'AreSponsorsMandatory'             => 'are_sponsors_mandatory:json_boolean',
        'AllowsAttachment'                 => 'allows_attachment:json_boolean',
        'AllowsLevel'                      => 'allows_level:json_boolean',
        'AllowsPublishingDates'            => 'allows_publishing_dates:json_boolean',
        'AllowsLocationTimeframeCollision' => 'allows_location_timeframe_collision:json_boolean',
        'AllowsLocation'                   => 'allows_location:json_boolean',
        'Default'                          => 'is_default:json_boolean',
        'SummitId'                         => 'summit_id:json_int',
        'ShowAlwaysOnSchedule'             => 'show_always_on_schedule:json_boolean',
    ];

    protected static $allowed_relations = [
        'summit_documents',
        'allowed_ticket_types',
        'summit',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $event_type = $this->object;
        if (!$event_type instanceof SummitEventType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('summit_documents', $relations) && !isset($values['summit_documents'])) {
            $summit_documents = [];
            if ($event_type->hasSummitDocuments()) {
                foreach ($event_type->getSummitDocuments() as $document) {
                    $summit_documents[] = $document->getId();
                }
            }
            $values['summit_documents'] = $summit_documents;
        }

        if(in_array('allowed_ticket_types', $relations) && !isset($values['allowed_ticket_types'])) {
            $allowed_ticket_types = [];
            foreach ($event_type->getAllowedTicketTypes() as $ticket_type) {
                $allowed_ticket_types[] = $ticket_type->getId();
            }
            $values['allowed_ticket_types'] = $allowed_ticket_types;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'summit' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'summit_id  ',
            'getter' => 'getSummit',
            'has' => 'hasSummit'
        ],
        'summit_documents' => [
            'serializer_type' => SerializerRegistry::SerializerType_Private,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSummitDocuments',
        ],
        'allowed_ticket_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Private,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedTicketTypes',
        ],
    ];
}