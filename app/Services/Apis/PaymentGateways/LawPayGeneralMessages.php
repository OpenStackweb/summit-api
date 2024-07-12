<?php namespace App\Services\Apis\PaymentGateways;
/*
 * Copyright 2022 OpenStack Foundation
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
 * Class LawPayGeneralMessages
 * @package App\Services\Apis\PaymentGateways
 * @see https://developers.affinipay.com/reference/api.html#GeneralMessages
 */
final class LawPayGeneralMessages {
  const not_authorized = "not_authorized";
  const not_authorized_country_denied = "not_authorized_country_denied";
  const malformed_request = "malformed_request";
  const invalid_request = "invalid_request";
  const no_content = "no_content";
  const invalid_data = "invalid_data";
  const invalid_data_encryption = "invalid_data_encryption";
  const resource_not_found = "resource_not_found";
  const unavailable_for_current_status = "unavailable_for_current_status";
  const no_account_specified = "no_account_specified";
  const merchant_not_active = "merchant_not_active";
  const unavailable_for_merchant_status = "unavailable_for_merchant_status";
  const account_not_active = "account_not_active";
  const unavailable_for_merchant_mode = "unavailable_for_merchant_mode";
  const unavailable_for_merchant_policy = "unavailable_for_merchant_policy";
  const no_payment_method = "no_payment_method";
  const no_account_for_payment_method = "no_account_for_payment_method";
  const incorrect_payment_type = "incorrect_payment_type";
  const payment_method_expired = "payment_method_expired";
  const search_failed = "search_failed";

  /**
   * @param string $code
   * @return bool
   */
  public static function isGeneralError(string $code): bool {
    return in_array($code, [
      self::not_authorized,
      self::not_authorized_country_denied,
      self::malformed_request,
      self::invalid_request,
      self::no_content,
      self::invalid_data,
      self::invalid_data_encryption,
      self::resource_not_found,
      self::unavailable_for_current_status,
      self::no_account_specified,
      self::merchant_not_active,
      self::unavailable_for_merchant_status,
      self::account_not_active,
      self::unavailable_for_merchant_mode,
      self::unavailable_for_merchant_policy,
      self::no_payment_method,
      self::no_account_for_payment_method,
      self::incorrect_payment_type,
      self::payment_method_expired,
      self::search_failed,
    ]);
  }
}
