<?php
namespace App\Http\Controllers;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfile;

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
 * Class PaymentGatewayProfileValidationRulesFactory
 * @package App\Http\Controllers
 */
final class PaymentGatewayProfileValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){

        if($update){
            $rules = [
                'active'             => 'sometimes|boolean',
                'application_type'   => 'sometimes|string|in:'.implode(',',IPaymentConstants::ValidApplicationTypes),
                'provider'           => 'required|string|in:'.implode(',',IPaymentConstants::ValidProviderTypes),
            ];
            if(isset($data['provider']) && $data['provider'] == IPaymentConstants::ProviderStripe){
                $rules = array_merge($rules, [
                    'test_mode_enabled'    => 'required|boolean',
                    'live_secret_key'      => 'sometimes|string',
                    'live_publishable_key' => 'required_with:live_secret_key|string',
                    'test_secret_key'      => 'required_with:test_mode_enabled|string',
                    'test_publishable_key' => 'required_with:test_secret_key|string',
                    'send_email_receipt'   => 'sometimes|boolean',
                ]);
            }
            return $rules;
        }

        $rules =  [
            'active'             => 'required|boolean',
            'application_type'   => 'required|string|in:'.implode(',',IPaymentConstants::ValidApplicationTypes),
            'provider'           => 'required|string|in:'.implode(',',IPaymentConstants::ValidProviderTypes),
        ];

        if(isset($data['provider']) && $data['provider'] == IPaymentConstants::ProviderStripe){
            $rules = array_merge($rules, [
                'test_mode_enabled'    => 'required|boolean',
                'live_secret_key'      => 'sometimes|string',
                'live_publishable_key' => 'required_with:live_secret_key|string',
                'test_secret_key'      => 'required_with:test_mode_enabled|string',
                'test_publishable_key' => 'required_with:test_secret_key|string',
                'send_email_receipt'   => 'sometimes|boolean',
            ]);
        }
        return $rules;
    }
}