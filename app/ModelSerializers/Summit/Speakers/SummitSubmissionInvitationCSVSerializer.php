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
use models\summit\SummitSubmissionInvitation;
/**
 * Class SummitSubmissionInvitationCSVSerializer
 * @package ModelSerializers
 */
class SummitSubmissionInvitationCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'SummitId' => 'summit_id:json_int',
        'FirstName' => 'first_name:json_string',
        'LastName' => 'last_name:json_string',
        'Email' => 'email:json_string',
        'Sent' => 'is_sent:jon_boolean',
        'SentDate' => 'sent_date:datetime_epoch',
    ];

    protected static $allowed_relations = [
        'tags',
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
        $invitation = $this->object;
        if (!$invitation instanceof SummitSubmissionInvitation) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values  = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('tags', $relations) && !isset($values['tags'])){
            $tags = [];
            foreach ($invitation->getTags() as $tag){
                $tags[] = $tag->getTag();
            }
            $values['tags'] = implode('|', $tags);
        }

        return $values;
    }
}