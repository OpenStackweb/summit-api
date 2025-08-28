<?php namespace ModelSerializers;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\RSVP;
/**
 * Class RSVPSerializer
 * @package ModelSerializers
 */
final class RSVPSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'OwnerId'             => 'owner_id:json_int',
        'EventId'             => 'event_id:json_int',
        'SeatType'            => 'seat_type:json_string',
        'Created'             => 'created:datetime_epoch',
        'ConfirmationNumber'  => 'confirmation_number:json_string',
        'EventUri'            => 'event_uri:json_string',
        'ActionSource'        => 'action_source:json_string',
        'ActionDate'          => 'action_date:datetime_epoch',
        'Status'              => 'status:json_string',
    ];

    protected function getSerializerType(?string $relation = null):string{
        $serializer_type = SerializerRegistry::SerializerType_Public;
        $current_member  = $this->resource_server_context->getCurrentUser();
        if(!is_null($current_member)){
            if($current_member->isAdmin()){
                $serializer_type = SerializerRegistry::SerializerType_Private;
            }
        }
        return $serializer_type;
    }


    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $rsvp = $this->object;
        if(! $rsvp instanceof RSVP) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $answers = [];
        foreach ($rsvp->getAnswers() as $answer){
            $answers[] = $answer->getId();
        }

        $values['answers'] = $answers;

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'owner': {
                        if(!$rsvp->hasOwner()) break;
                        unset($values['owner_id']);
                        $values['owner'] = SerializerRegistry::getInstance()->getSerializer
                        (
                            $rsvp->getOwner(),
                            $this->getSerializerType($relation)
                        )->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                        );
                    }
                        break;
                    case 'event': {
                        if(!$rsvp->hasEvent()) break;
                        unset($values['event_id']);
                        $values['event'] = SerializerRegistry::getInstance()->getSerializer
                        (
                            $rsvp->getEvent(),
                            $this->getSerializerType($relation)
                        )->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                        );
                    }
                    break;
                    case 'answers':{
                        $answers = [];
                        foreach ($rsvp->getAnswers() as $answer){
                            $answers[] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $answer,
                                $this->getSerializerType($relation)
                            )->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['answers'] = $answers;
                    }
                    break;
                }
            }
        }

        return $values;
    }

}