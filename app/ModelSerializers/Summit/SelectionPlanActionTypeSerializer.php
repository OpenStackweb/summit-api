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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\PresentationActionType;
use models\summit\SelectionPlanActionType;

/**
 * Class SelectionPlanActionTypeSerializer
 * @package ModelSerializers
 */
final class SelectionPlanActionTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Label'             => 'label:json_string',
        'SelectionPlanId'   => 'selection_plan_id:json_int',
        'Order'             => 'order:json_int',
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
        $action = $this->object;
        if (!$action instanceof SelectionPlanActionType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'summit':
                        {
                            unset($values['summit_id']);
                            $values['summit'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $action->getSummit()
                            )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                    case 'selection_plan':
                        {
                            unset($values['selection_plan_id']);
                            $values['selection_plan'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $action->getSelectionPlan()
                            )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                }
            }
        }
        return $values;
    }
}