<?php namespace App\Services\Model\Imp;
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
use models\summit\PaymentGatewayProfileFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IPaymentGatewayProfileService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PaymentGatewayProfile;
use models\summit\Summit;
/**
 * Class PaymentGatewayProfileService
 * @package App\Services\Model\Imp
 */
final class PaymentGatewayProfileService
    extends AbstractService
    implements IPaymentGatewayProfileService
{

    /**
     * @inheritDoc
     */
    public function addPaymentProfile(Summit $summit, array $payload): ?PaymentGatewayProfile
    {
        return $this->tx_service->transaction(function() use($summit, $payload){
            $profile = PaymentGatewayProfileFactory::build($payload['provider'], $payload);
            $formerProfile = $summit->getPaymentGateWayProfilePerApp($profile->getApplicationType());
            if($profile->isActive() && !is_null($formerProfile) && $formerProfile->isActive()){
                throw new ValidationException
                (
                    sprintf("There is already an active Payment Profile for application type %s,", $formerProfile->getApplicationType())
                );
            }
            $summit->addPaymentProfile($profile);
            if(isset($payload['active']) && boolval($payload['active']) == true){
                // force activation ( rebuild web hook)
                $profile->activate();
            }
            return $profile;
        });
    }

    /**
     * @inheritDoc
     */
    public function deletePaymentProfile(Summit $summit, int $child_id): void
    {
        $this->tx_service->transaction(function() use($summit, $child_id){
            $profile = $summit->getPaymentProfileById($child_id);
            if(is_null($profile))
                throw new EntityNotFoundException();
            $summit->removePaymentProfile($profile);
        });
    }

    /**
     * @inheritDoc
     */
    public function updatePaymentProfile(Summit $summit, int $child_id, array $payload): ?PaymentGatewayProfile
    {
        return $this->tx_service->transaction(function() use($summit, $child_id, $payload){

            $profile = $summit->getPaymentProfileById($child_id);
            if(is_null($profile))
                throw new EntityNotFoundException();

            $formerProfile = $summit->getPaymentGateWayProfilePerApp($profile->getApplicationType());
            // if we are activating this profile, check if there is not already one activated
            if(isset($payload['active']) && boolval($payload['active']) == true &&
                !is_null($formerProfile) && $formerProfile->getId() != $profile->getId()
                && $formerProfile->isActive()){
                throw new ValidationException
                (
                    sprintf("There is already an active Payment Profile for application type %s.", $formerProfile->getApplicationType())
                );
            }

            $profile = PaymentGatewayProfileFactory::populate($profile, $payload);

            return $profile;
        });
    }
}