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

use App\Services\Model\AbstractService;
use App\Services\Model\IPaymentGatewayProfileService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfile;
use models\summit\PaymentGatewayProfileFactory;
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
        // Populating a profile can already create its webhook at the payment gateway, and a DB
        // rollback cannot undo that. Every profile built here is tracked so that its webhooks can
        // be deleted again when it does not end up stored, including the ones built by an
        // attempt that the root transaction re-ran after a transient DB error.
        $built_profiles = [];
        try {
            $profile = $this->tx_service->transaction(function () use ($summit, $payload, &$built_profiles) {
                Log::debug("PaymentGatewayProfileService::addPaymentProfilee", ["summit" => $summit->getId(), 'payload' => $payload]);
                $is_active = isset($payload['active']) && boolval($payload['active']) == true;
                // check before building the profile, so that a rejected one never reaches the gateway
                if ($is_active) {
                    $formerProfile = $summit->getPaymentGateWayProfilePerApp
                    (
                        $payload['application_type'] ?? IPaymentConstants::ApplicationTypeRegistration
                    );
                    if (!is_null($formerProfile) && $formerProfile->isActive()) {
                        throw new ValidationException
                        (
                            sprintf("There is already an active Payment Profile for application type %s,", $formerProfile->getApplicationType())
                        );
                    }
                }
                $payload['summit'] = $summit;
                $profile = PaymentGatewayProfileFactory::create($payload['provider']);
                $built_profiles[] = $profile;
                PaymentGatewayProfileFactory::populate($profile, $payload);
                $summit->addPaymentProfile($profile);
                if ($is_active) {
                    // force activation ( rebuild web hook )
                    $profile->activate();
                }
                return $profile;
            });
        } catch (\Exception $ex) {
            foreach ($built_profiles as $built_profile)
                $built_profile->discardCreatedWebHooks();
            throw $ex;
        }

        foreach ($built_profiles as $built_profile) {
            if ($built_profile !== $profile)
                $built_profile->discardCreatedWebHooks();
        }
        return $profile;
    }

    /**
     * @inheritDoc
     */
    public function deletePaymentProfile(Summit $summit, int $child_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $child_id) {
            Log::debug("PaymentGatewayProfileService::deletePaymentProfile", ["summit" => $summit->getId(), "profile_id" => $child_id]);

            $profile = $summit->getPaymentProfileById($child_id);
            if (is_null($profile))
                throw new EntityNotFoundException();
            $summit->removePaymentProfile($profile);
        });
    }

    /**
     * @inheritDoc
     */
    public function updatePaymentProfile(Summit $summit, int $child_id, array $payload): ?PaymentGatewayProfile
    {
        return $this->tx_service->transaction(function () use ($summit, $child_id, $payload) {
            Log::debug("PaymentGatewayProfileService::updatePaymentProfile", ["summit" => $summit->getId(), "profile_id" => $child_id, "payload" => $payload]);
            $profile = $summit->getPaymentProfileById($child_id);
            if (is_null($profile))
                throw new EntityNotFoundException();

            $formerProfile = $summit->getPaymentGateWayProfilePerApp($profile->getApplicationType());
            // if we are activating this profile, check if there is not already one activated
            if (isset($payload['active']) && boolval($payload['active']) == true &&
                !is_null($formerProfile) && $formerProfile->getId() != $profile->getId()
                && $formerProfile->isActive()) {
                throw new ValidationException
                (
                    sprintf("There is already an active Payment Profile for application type %s.", $formerProfile->getApplicationType())
                );
            }
            $payload['summit'] = $summit;
            return PaymentGatewayProfileFactory::populate($profile, $payload);
        });
    }
}
