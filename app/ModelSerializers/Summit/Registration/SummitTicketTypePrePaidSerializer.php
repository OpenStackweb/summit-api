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

use models\summit\SummitTicketTypePrePaid;

/**
 * Class SummitTicketTypePrePaidSerializer
 * @package ModelSerializers
 */
final class SummitTicketTypePrePaidSerializer extends SummitTicketTypeWithPromoSerializer
{
    /**
     * @param $entity
     * @param array $values
     * @return array
     */
    protected function serializeCustomFields($entity, $values): array {
        return $values;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $ticket_type = $this->object;
        if (!$ticket_type instanceof SummitTicketTypePrePaid) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        return $this->serializeCustomFields($ticket_type, $values);
    }
}