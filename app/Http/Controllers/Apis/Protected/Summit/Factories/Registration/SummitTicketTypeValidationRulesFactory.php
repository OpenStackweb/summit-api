<?php namespace App\Http\Controllers;
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
use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use models\summit\SummitTicketType;
/**
 * Class SummitTicketTypeValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitTicketTypeValidationRulesFactory extends AbstractValidationRulesFactory
{
    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'name'                   => 'required|string',
            'cost'                   => 'sometimes|numeric|greater_than_or_equal:0',
            'currency'               => 'required_with:cost|string|currency_iso',
            'quantity_2_sell'        => 'sometimes|integer|greater_than_or_equal:0',
            'max_quantity_per_order' => 'sometimes|integer|greater_than_or_equal:0',
            'sales_start_date'       => 'nullable|date_format:U|epoch_seconds',
            'sales_end_date'         => 'nullable:sales_start_date|date_format:U|epoch_seconds|after:sales_start_date',
            'description'            => 'sometimes|string',
            'external_id'            => 'sometimes|string|max:255',
            'badge_type_id'          => 'sometimes|integer',
            'audience'               => 'sometimes|string|in:'.implode(',', SummitTicketType::AllowedAudience),
            'allows_to_delegate'    => 'sometimes|boolean',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'name'                    => 'sometimes|string',
            'description'             => 'sometimes|string',
            'badge_type_id'           => 'sometimes|integer',
            'external_id'             => 'sometimes|string|max:255',
            'currency'                => 'sometimes|string|currency_iso',
            'quantity_2_sell'         => 'sometimes|integer|greater_than_or_equal:0',
            'max_quantity_per_order'  => 'sometimes|integer|greater_than_or_equal:0',
            'sales_start_date'        => 'nullable|date_format:U|epoch_seconds',
            'sales_end_date'          => 'nullable:sales_start_date|date_format:U|epoch_seconds|after:sales_start_date',
            'cost'                    => 'sometimes|numeric|greater_than_or_equal:0',
            'audience'                => 'sometimes|string|in:'.implode(',', SummitTicketType::AllowedAudience),
            'allows_to_delegate'      => 'sometimes|boolean',
        ];
    }
}