<?php namespace App\ModelSerializers\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class StripePaymentProfileSerializer
 * @package App\ModelSerializers\Summit
 */
class StripePaymentProfileSerializer extends PaymentGatewayProfileSerializer
{
    protected static $array_mappings = [
        'TestModeEnabled'     => 'test_mode_enabled:json_boolean',
        'LivePublishableKey'  => 'live_publishable_key:json_string',
        'TestPublishableKey'  => 'test_publishable_key:json_string',
    ];
}