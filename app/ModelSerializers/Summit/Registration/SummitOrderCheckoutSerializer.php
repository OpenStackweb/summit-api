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
 * Class SummitOrderCheckoutSerializer
 * @package App\ModelSerializers
 */
class SummitOrderCheckoutSerializer extends SummitOrderReservationSerializer
{

    protected static $array_mappings = [
        'BillingAddress1'               => 'billing_address_1:json_string',
        'BillingAddress2'               => 'billing_address_2:json_string',
        'BillingAddressZipCode'         => 'billing_address_zip_code:json_string',
        'BillingAddressCity'            => 'billing_address_city:json_string',
        'BillingAddressState'           => 'billing_address_state:json_string',
        'BillingAddressCountryIsoCode'  => 'billing_address_country_iso_code:json_string',
    ];
}