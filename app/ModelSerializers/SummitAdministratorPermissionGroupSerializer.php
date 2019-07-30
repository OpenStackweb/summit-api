<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\main\SummitAdministratorPermissionGroup;

/**
 * Class SummitAdministratorPermissionGroupSerializer
 * @package ModelSerializers
 */
final class SummitAdministratorPermissionGroupSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Title' => 'title:json_string',
    ];

    protected static $allowed_relations = [
        'members',
        'summits',
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
        $group  = $this->object;
        if(!$group instanceof SummitAdministratorPermissionGroup) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('members', $relations)) {
            $members = [];
            foreach ($group->getMembersIds() as $member_id)
                $members[] = intval($member_id) ;
            $values['members'] = $members;
        }

        if(in_array('summits', $relations)) {
            $summits = [];
            foreach ($group->getSummitsIds() as $summit_id){
                $summits[] = intval($summit_id);
            }
            $values['summits'] = $summits;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'members': {
                        if(!in_array('members', $relations)) break;
                        $members = [];
                        unset($values['members']);
                        foreach ($group->getMembers() as $m) {
                            $members[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand,'members'));
                        }
                        $values['members'] = $members;
                    }
                        break;
                    case 'summits': {
                        if(!in_array('summits', $relations)) break;
                        $summits = [];
                        unset($values['summits']);
                        foreach ($group->getSummits() as $s) {
                            $summits[] = SerializerRegistry::getInstance()->getSerializer($s)->serialize(AbstractSerializer::filterExpandByPrefix($expand,'summits'));
                        }
                        $values['summits'] = $summits;
                    }
                        break;
                }
            }
        }
        return $values;
    }

}