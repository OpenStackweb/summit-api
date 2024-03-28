<?php namespace ModelSerializers;
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

use App\ModelSerializers\Traits\RequestScopedCache;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitTicketType;

/**
 * Class SummitTicketTypeSerializer
 * @package ModelSerializers
 */
class SummitTicketTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Description' => 'description:json_string',
        'ExternalId' => 'external_id:json_string',
        'SummitId' => 'summit_id:json_int',
        'Cost' => 'cost:json_float',
        'Currency' => 'currency:json_string',
        'CurrencySymbol' => 'currency_symbol:json_string',
        'Quantity2Sell' => 'quantity_2_sell:json_int',
        'MaxQuantityPerOrder' => 'max_quantity_per_order:json_int',
        'SalesStartDate' => 'sales_start_date:datetime_epoch',
        'SalesEndDate' => 'sales_end_date:datetime_epoch',
        'BadgeTypeId' => 'badge_type_id:json_int',
        'QuantitySold' => 'quantity_sold:json_int',
        'Audience' => 'audience:json_string',
    ];

    protected static $allowed_relations = [
        'applied_taxes',
    ];

    use RequestScopedCache;

    use SummitTicketTypeCommonSerializer;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        return $this->cache(
            $this->getRequestKey
            (
                "SummitTicketTypeSerializer",
                $this->object->getIdentifier(),
                $expand,
                $fields,
                $relations
            ), function () use ($expand, $fields, $relations, $params) {

            $ticket_type = $this->object;
            if (!$ticket_type instanceof SummitTicketType) return [];
            $values = parent::serialize($expand, $fields, $relations, $params);

            return self::serializeCommonFields($ticket_type, $values, $expand, $fields, $relations, $params);
        });

    }
}