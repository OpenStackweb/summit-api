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
use models\summit\SummitRegistrationDiscountCodeTicketTypeRule;

/**
 * Class SummitRegistrationDiscountCodeTicketTypeRuleSerializer
 * @package ModelSerializers
 */
final class SummitRegistrationDiscountCodeTicketTypeRuleSerializer extends AbstractSerializer {
  protected static $array_mappings = [
    "Id" => "id:json_int",
    "Rate" => "rate:json_float",
    "Amount" => "amount:json_float",
    "TicketTypeId" => "ticket_type_id:json_int",
    "DiscountCodeId" => "discount_code_id:json_int",
  ];

  /**
   * @param null $expand
   * @param array $fields
   * @param array $relations
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $rule = $this->object;
    if (!$rule instanceof SummitRegistrationDiscountCodeTicketTypeRule) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "ticket_type":
            unset($values["ticket_type_id"]);
            $values["ticket_type"] = SerializerRegistry::getInstance()
              ->getSerializer($rule->getTicketType())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
            break;
          case "discount_code":
            unset($values["discount_code_id"]);
            $values["discount_code"] = SerializerRegistry::getInstance()
              ->getSerializer($rule->getDiscountCode())
              ->serialize(
                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                $params,
              );
            break;
        }
      }
    }

    return $values;
  }
}
