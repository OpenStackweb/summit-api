<?php namespace App\ModelSerializers\Summit;
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
 * Class AdminLawPayPaymentProfileSerializer
 * @package App\ModelSerializers\Summit
 */
final class AdminLawPayPaymentProfileSerializer extends LawPayPaymentProfileSerializer {
  protected static $array_mappings = [
    "LiveSecretKey" => "live_secret_key:json_string",
    "TestSecretKey" => "test_secret_key:json_string",
    "MerchantAccountId" => "merchant_account_id:json_string",
  ];
}
