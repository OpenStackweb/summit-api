<?php namespace models\summit;
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

use models\exceptions\ValidationException;
/**
 * Class PaymentGatewayProfileFactory
 * @package models\summit
 */
final class PaymentGatewayProfileFactory
{
    /**
     * @param string $provider
     * @param array $params
     * @return PaymentGatewayProfile|null
     * @throws \models\exceptions\ValidationException
     */
    public static function build(string $provider, array $params): ?PaymentGatewayProfile
    {
        $profile = null;
        if ($provider == IPaymentConstants::ProviderStripe) {
            $profile = static::populate(new StripePaymentProfile, $params);
        }

        if ($provider == IPaymentConstants::ProviderLawPay) {
            $profile = static::populate(new LawPayPaymentProfile, $params);
        }

        if(is_null($profile))
            throw new ValidationException("Profile type does not exists.");

        return $profile;
    }

    /**
     * @param PaymentGatewayProfile $profile
     * @param array $params
     * @return PaymentGatewayProfile
     * @throws \models\exceptions\ValidationException
     */
    public static function populate(PaymentGatewayProfile $profile, array $params): PaymentGatewayProfile
    {
        $test_publishable_key = $params['test_publishable_key'] ?? null;
        $test_secret_key      = $params['test_secret_key'] ?? null;

        if(isset($params['summit']))
        {
            $profile->setSummit($params['summit']);
        }

        $profile->setTestKeys([
            'publishable_key' => $test_publishable_key,
            'secret_key'      => $test_secret_key
        ]);

        $live_publishable_key = $params['live_publishable_key'] ?? null;
        $live_secret_key      = $params['live_secret_key'] ?? null;
        $profile->setLiveKeys(
            [
                'publishable_key' => $live_publishable_key,
                'secret_key' => $live_secret_key,
            ]
        );

        if (isset($params['test_mode_enabled']))
            boolval($params['test_mode_enabled']) == true ? $profile->setTestMode() : $profile->setLiveMode();

        if( $profile instanceof LawPayPaymentProfile){
            if(isset($params['merchant_account_id']))
                $profile->setMerchantAccountId(trim($params['merchant_account_id']));
        }

        if ($profile instanceof StripePaymentProfile) {

            $profile->setTestWebhookSecretKey($params['test_web_hook_secret'] ?? '');
            $profile->setLiveWebhookSecretKey($params['live_web_hook_secret'] ?? '');

            if (isset($params['send_email_receipt']))
                $profile->setSendEmailReceipt(boolval($params['send_email_receipt']));
        }

        // common properties
        if (isset($params['application_type']))
            $profile->setApplicationType($params['application_type']);

        if (isset($params['active']))
            boolval(['active']) == true ? $profile->activate() : $profile->disable();

        return $profile;
    }
}