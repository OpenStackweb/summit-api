<?php namespace ModelSerializers;
/**
 * Copyright 2019 OpenStack Foundation
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
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitAttendeeTicketTax;
/**
 * Class SummitAttendeeTicketTaxSerializer
 * @package ModelSerializers
 */
final class SummitAttendeeTicketTaxSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Id' => 'id:json_int',
        'Amount'   => 'amount:json_float',
        'AmountInCents' => 'amount_in_cents:json_int',
        'TaxId'    => 'tax_id:json_int',
        'TicketId' => 'ticket_id:json_int',
    ];

    protected static $expand_mappings = [
        'tax' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'tax_id',
            'getter' => 'getTax',
            'has' => 'hasTax'
        ],
        'ticket' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'ticket_id',
            'getter' => 'getTicket',
            'has' => 'hasTicket'
        ],
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
        $tax_applied = $this->object;
        if (!$tax_applied instanceof SummitAttendeeTicketTax) return [];
        return parent::serialize($expand, $fields, $relations, $params);
    }

}