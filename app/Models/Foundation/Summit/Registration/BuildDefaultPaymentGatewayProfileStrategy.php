<?php namespace App\Models\Foundation\Summit\Registration;
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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\PaymentGatewayProfileFactory;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfile;
/**
 * Class BuildDefaultPaymentGatewayProfileStrategy
 * @package App\Models\Foundation\Summit\Registration
 */
final class BuildDefaultPaymentGatewayProfileStrategy implements
  IBuildDefaultPaymentGatewayProfileStrategy {
  public function build(string $application_type): ?PaymentGatewayProfile {
    try {
      Log::debug(
        sprintf(
          "BuildDefaultPaymentGatewayProfileStrategy::build application_type %s",
          $application_type,
        ),
      );

      if ($application_type == IPaymentConstants::ApplicationTypeRegistration) {
        $provider = Config::get("registration.default_payment_provider");
        if (empty($provider)) {
          throw new ValidationException(
            sprintf("Missing Provider settings for application %s", $application_type),
          );
        }
        $config = Config::get("registration.default_payment_provider_config");
        $provider_config = $config[$provider] ?? [];
        if (!count($provider_config)) {
          throw new ValidationException(
            sprintf(
              "Missing configuration for provider %s and application %s",
              $provider,
              $application_type,
            ),
          );
        }

        return PaymentGatewayProfileFactory::build(
          $provider,
          array_merge($provider_config, [
            "active" => true,
            "application_type" => $application_type,
            "set_webhooks" => true,
          ]),
        );
      }

      if ($application_type == IPaymentConstants::ApplicationTypeBookableRooms) {
        $provider = Config::get("bookable_rooms.default_payment_provider");
        if (empty($provider)) {
          throw new ValidationException(
            sprintf("Missing Provider settings for application %s", $application_type),
          );
        }
        $config = Config::get("bookable_rooms.default_payment_provider_config");
        $provider_config = $config[$provider] ?? [];
        if (!count($provider_config)) {
          throw new ValidationException(
            sprintf(
              "Missing configuration for provider %s and application %s",
              $provider,
              $application_type,
            ),
          );
        }
        return PaymentGatewayProfileFactory::build(
          $provider,
          array_merge($provider_config, [
            "active" => true,
            "application_type" => $application_type,
            "set_webhooks" => true,
          ]),
        );
      }
    } catch (\Exception $ex) {
      Log::warning($ex);
    }

    return null;
  }
}
