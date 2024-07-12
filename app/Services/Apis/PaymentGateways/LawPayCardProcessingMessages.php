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
 * Class LawPayCardProcessingMessages
 * @package App\Services\Apis\PaymentGateways
 * @see https://developers.affinipay.com/reference/api.html#CardProcessingMessages
 */
final class LawPayCardProcessingMessages {
  const no_card_details_or_token_present = "no_card_details_or_token_present";
  const not_valid_for_transaction_status = "not_valid_for_transaction_status";
  const unavailable_due_to_capture_in_process = "unavailable_due_to_capture_in_process";
  const exceeds_authorized_amount = "exceeds_authorized_amount";
  const refund_exceeds_transaction = "refund_exceeds_transaction";
  const currency_mismatch = "currency_mismatch";
  const unsupported_currency = "unsupported_currency";
  const card_declined = "card_declined";
  const card_declined_processing_error = "card_declined_processing_error";
  const card_declined_insufficient_funds = "card_declined_insufficient_funds";
  const card_declined_limit_exceeded = "card_declined_limit_exceeded";
  const card_declined_refer_to_issuer = "card_declined_refer_to_issuer";
  const card_declined_hold = "card_declined_hold";
  const card_declined_no_account = "card_declined_no_account";
  const card_type_not_accepted = "card_type_not_accepted";
  const merchant_trans_max_amount_exceeded = "merchant_trans_max_amount_exceeded";
  const merchant_trans_daily_count_exceeded = "merchant_trans_daily_count_exceeded";
  const merchant_trans_daily_amount_exceeded = "merchant_trans_daily_amount_exceeded";
  const merchant_trans_monthly_count_exceeded = "merchant_trans_monthly_count_exceeded";
  const merchant_trans_monthly_amount_exceeded = "merchant_trans_monthly_amount_exceeded";
  const card_processor_not_available = "card_processor_not_available";
  const card_processing_error = "card_processing_error";
  const settlement_failed = "settlement_failed";

  /**
   * @param string $code
   * @return bool
   */
  public static function isCardProcessingError(string $code): bool {
    return in_array($code, [
      self::no_card_details_or_token_present,
      self::not_valid_for_transaction_status,
      self::unavailable_due_to_capture_in_process,
      self::exceeds_authorized_amount,
      self::refund_exceeds_transaction,
      self::currency_mismatch,
      self::unsupported_currency,
      self::card_declined,
      self::card_declined_processing_error,
      self::card_declined_insufficient_funds,
      self::card_declined_limit_exceeded,
      self::card_declined_refer_to_issuer,
      self::card_declined_hold,
      self::card_declined_no_account,
      self::card_type_not_accepted,
      self::merchant_trans_max_amount_exceeded,
      self::merchant_trans_daily_count_exceeded,
      self::merchant_trans_daily_amount_exceeded,
      self::merchant_trans_monthly_count_exceeded,
      self::merchant_trans_monthly_amount_exceeded,
      self::card_processor_not_available,
      self::card_processing_error,
      self::settlement_failed,
    ]);
  }
}
