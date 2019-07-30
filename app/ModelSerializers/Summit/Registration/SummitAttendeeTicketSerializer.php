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
use models\summit\SummitAttendeeTicket;
/**
 * Class SummitAttendeeTicketSerializer
 * @package ModelSerializers
 */
class SummitAttendeeTicketSerializer extends BaseSummitAttendeeTicketSerializer
{   /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $ticket = $this->object;
        if (!$ticket instanceof SummitAttendeeTicket) return [];
        $values   = parent::serialize($expand, $fields, $relations, $params);

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'order': {
                        if(!$ticket->hasOrder()) continue;
                        unset($values['order_id']);
                        $values['order'] = SerializerRegistry::getInstance()->getSerializer($ticket->getOrder())->serialize(AbstractSerializer::getExpandForPrefix('order', $expand));
                    }
                    break;
                }

            }
        }
        return $values;
    }
}