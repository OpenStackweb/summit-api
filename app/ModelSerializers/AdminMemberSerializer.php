<?php namespace ModelSerializers;
use Libs\ModelSerializers\AbstractSerializer;
use models\main\Member;

/**
 * Copyright 2020 OpenStack Foundation
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

/**
 * Class AdminMemberSerializer
 * @package ModelSerializers
 */
class AdminMemberSerializer extends AbstractMemberSerializer
{
    protected static $array_mappings = [
        'Email' => 'email:json_string',
        'SecondEmail' => 'second_email:json_string',
        'ThirdEmail' => 'third_email:json_string',
        'UserExternalId' => 'user_external_id:json_int'
    ];

    protected static $allowed_relations = [
        'rsvp',
        'rsvp_invitations',
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
        $member         = $this->object;
        if(!$member instanceof Member) return [];

        $values           = parent::serialize($expand, $fields, $relations, $params);
        $summit           = isset($params['summit'])? $params['summit'] :null;

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'rsvp':{
                        if(!in_array('rsvp', $relations)) break;
                        if(is_null($summit)) break;
                        $rsvps = [];
                        foreach ($member->getRsvpBySummit($summit) as $rsvp){
                            $rsvps[] = SerializerRegistry::getInstance()
                                ->getSerializer($rsvp)
                                ->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['rsvp'] = $rsvps;
                    }
                        break;
                    case 'rsvp_invitations':{
                        if(!in_array('rsvp_invitations', $relations)) break;
                        if(is_null($summit)) break;
                        $rsvp_invitations = [];
                        foreach ($member->getRSVPInvitations($summit) as $rsvp_invitation){
                            $rsvp_invitations[] = SerializerRegistry::getInstance()
                                ->getSerializer($rsvp_invitation)
                                ->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['rsvp_invitations'] = $rsvp_invitations;
                    }
                }
            }
        }
        return $values;
    }
}