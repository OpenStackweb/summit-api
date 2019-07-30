<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\SummitRegistrationDiscountCodeTicketTypeRule;
/**
 * Class SummitRegistrationDiscountCodeTicketTypeRuleFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitRegistrationDiscountCodeTicketTypeRuleFactory
{
    /**
     * @param array $data
     * @return SummitRegistrationDiscountCodeTicketTypeRule
     */
    public static function build(array $data){
        return self::populate(new SummitRegistrationDiscountCodeTicketTypeRule, $data);
    }

    /**
     * @param SummitRegistrationDiscountCodeTicketTypeRule $rule
     * @param array $data
     * @return SummitRegistrationDiscountCodeTicketTypeRule
     */
    public static function populate(SummitRegistrationDiscountCodeTicketTypeRule $rule, array $data){

        if(isset($data['amount']))
            $rule->setAmount(floatval($data['amount']));
        if(isset($data['rate']))
            $rule->setRate(floatval($data['rate']));
        if(isset($data['ticket_type']))
            $rule->setTicketType($data['ticket_type']);
        return $rule;
    }
}