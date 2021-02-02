<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitTrackChair;
/**
 * Class SummitTrackChairCSVSerializer
 * @package ModelSerializers
 */
final class SummitTrackChairCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'SummitId' => 'summit_id:json_int',
        'MemberId' => 'member_id:json_int',
    ];

    protected static $allowed_fields = [
        'summit_id',
        'member_id',
    ];

    protected static $allowed_relations = [
        'categories',
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
        if (!count($relations)) $relations = $this->getAllowedRelations();

        $track_chair = $this->object;

        if (!$track_chair instanceof SummitTrackChair) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        $values['member_first_name'] = $track_chair->getMember()->getFirstName();
        $values['member_last_name'] = $track_chair->getMember()->getLastName();
        $values['member_email'] = $track_chair->getMember()->getEmail();

        if (in_array('categories', $relations)) {
            $categories = [];
            foreach ($track_chair->getCategories() as $t) {
                $categories[] = $t->getTitle();
            }
            $values['categories'] = implode("|", $categories);
        }

        return $values;
    }
}