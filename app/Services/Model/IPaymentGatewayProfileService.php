<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PaymentGatewayProfile;
use models\summit\Summit;
/**
 * Interface IPaymentGatewayProfileService
 * @package App\Services\Model
 */
interface IPaymentGatewayProfileService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return PaymentGatewayProfile|null
     * @throws ValidationException
     */
    public function addPaymentProfile(Summit $summit, array $payload):?PaymentGatewayProfile;

    /**
     * @param Summit $summit
     * @param int $child_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deletePaymentProfile(Summit $summit, int $child_id):void;

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return PaymentGatewayProfile|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updatePaymentProfile(Summit $summit, int $child_id, array $payload):?PaymentGatewayProfile;
}