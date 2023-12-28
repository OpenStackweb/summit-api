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

use libs\utils\JsonUtils;
use models\summit\SummitTicketTypeWithPromo;

/**
 * Class SummitTicketTypeWithPromoSerializer
 * @package ModelSerializers
 */
class SummitTicketTypeWithPromoSerializer extends SilverStripeSerializer
{
    use SummitTicketTypeCommonSerializer;

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

    /**
     * @param $entity
     * @param array $values
     * @return array
     */
    protected function serializeCustomFields($entity, $values): array {
        $values["cost_with_applied_discount"] = JsonUtils::toJsonFloat($entity->getCostWithAppliedDiscount());
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
        if (!$ticket_type instanceof SummitTicketTypeWithPromo) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $values = self::serializeCommonFields($ticket_type, $values, $expand, $fields, $relations, $params);
        return $this->serializeCustomFields($ticket_type, $values);
    }
}