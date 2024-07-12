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
 * @see https://developers.affinipay.com/reference/api.html#charge
 */
interface ILawPayApiChargeStatus {
  /**
   * The Gateway is awaiting confirmation from the processor of the transaction's creation.
   */
  const Pending = "PENDING";
  /**
   * The transaction has been authorized.
   */
  const Authorized = "AUTHORIZED";
  /**
   * The Gateway has completed processing the transaction. Depending on the configuration and type of underlying
   * Merchant or eCheck Account, settlement is either in process or complete.
   */
  const Completed = "COMPLETED";
  /**
   * The transaction has been voided
   */
  const Voided = "VOIDED";
  /**
   * The transaction failed. Consult the failure code for details.
   */
  const Failed = "FAILED";
}
