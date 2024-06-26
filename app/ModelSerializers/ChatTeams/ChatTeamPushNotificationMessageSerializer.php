<?php namespace ModelSerializers\ChatTeams;
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
use models\main\ChatTeamPushNotificationMessage;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class ChatTeamPushNotificationMessageSerializer
 * @package ModelSerializers\ChatTeams
 */
final class ChatTeamPushNotificationMessageSerializer  extends SilverStripeSerializer
{
    protected static $array_mappings = array
    (
        'TeamId'     => 'team_id:json_int',
        'OwnerId'    => 'owner_id:json_int',
        'Priority'   => 'priority:json_string',
        'Message'    => 'body:json_string',
    );

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $message = $this->object;
        if(! $message instanceof ChatTeamPushNotificationMessage) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if($message->isSent()){
            $values['sent_date'] = $message->getSentDate()->getTimestamp();
        }

        if (!empty($expand)) {
            $expand = explode(',', $expand);
            foreach ($expand as $relation) {
                switch (trim($relation)) {
                    case 'owner':{
                        if(isset($values['owner_id']))
                        {
                            unset($values['owner_id']);
                            $values['owner'] =  SerializerRegistry::getInstance()->getSerializer($message->getOwner())->serialize();
                        }
                    }
                    break;
                    case 'team':{
                        if(isset($values['team_id']))
                        {
                            unset($values['team_id']);
                            $values['team'] =  SerializerRegistry::getInstance()->getSerializer($message->getTeam())->serialize();
                        }
                    }
                    break;
                }
            }
        }

        return $values;
    }

}