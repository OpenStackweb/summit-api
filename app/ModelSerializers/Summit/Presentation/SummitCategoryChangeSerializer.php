<?php namespace ModelSerializers;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitCategoryChange;

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


/**
 * Class SummitCategoryChangeSerializer
 * @package ModelSerializers
 */
final class SummitCategoryChangeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Reason' => 'reason:json_string',
        'ApprovalDate' => 'approval_date:datetime_epoch',
        'NiceStatus' => 'status:json_string',
        'PresentationId' => 'presentation_id:json_int',
        'NewCategoryId' => 'new_category_id:json_int',
        'OldCategoryId' => 'old_category_id:json_int',
        'RequesterId' => 'requester_id:json_int',
        'AproverId' => 'aprover_id:json_int',
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
        $request = $this->object;
        if (!$request instanceof SummitCategoryChange) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'presentation':
                        {
                            unset($values['presentation_id']);
                            $values['presentation'] = SerializerRegistry::getInstance()->getSerializer($request->getPresentation())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                    case 'new_category':
                        {
                            unset($values['new_category_id']);
                            $values['new_category'] = SerializerRegistry::getInstance()->getSerializer($request->getNewCategory())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                    case 'old_category':
                        {
                            unset($values['old_category_id']);
                            $values['old_category'] = SerializerRegistry::getInstance()->getSerializer($request->getOldCategory())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                    case 'requester':
                        {
                            unset($values['requester_id']);
                            $values['requester'] = SerializerRegistry::getInstance()->getSerializer($request->getRequester())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        break;
                    case 'aprover':
                        {
                            if($request->hasAprover()) {
                                unset($values['aprover_id']);
                                $values['aprover'] = SerializerRegistry::getInstance()->getSerializer($request->getAprover())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                        }
                        break;
                }
            }
        }
        return $values;
    }
}