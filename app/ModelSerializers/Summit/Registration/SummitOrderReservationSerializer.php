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


/**
 * Class SummitOrderReservationSerializer
 * @package App\ModelSerializers
 */
class SummitOrderReservationSerializer extends SummitOrderBaseSerializer
{
    protected static $array_mappings = [
        'RawAmount'                 => 'raw_amount:json_float',
        'FinalAmount'               => 'amount:json_float',
        'TaxesAmount'               => 'taxes_amount:json_float',
        'DiscountAmount'            => 'discount_amount:json_float',
        'PaymentGatewayClientToken' => 'payment_gateway_client_token:json_string',
        'PaymentGatewayCartId'      => 'payment_gateway_cart_id:json_string',
        'Hash'                      => 'hash:json_string',
        'HashCreationDate'          => 'hash_creation_date:datetime_epoch',
    ];
}