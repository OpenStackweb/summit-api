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
use services\utils\AmbiguousCommitException;

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
        // be deleted again when it does not end up stored: a failed transaction, an attempt that
        // the root transaction re-ran after a transient DB error, or a returned profile that the
        // transaction never inserted.
        $built_profiles = [];
        try {
            $profile = $this->tx_service->transaction(function () use ($summit, $payload, &$built_profiles) {
                Log::debug("PaymentGatewayProfileService::addPaymentProfilee", ["summit" => $summit->getId(), 'payload' => PaymentGatewayProfile::toLogContext($payload)]);
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
        } catch (AmbiguousCommitException $ex) {
            // The COMMIT of the last attempt was sent but never acknowledged, so its profile may
            // be stored, and its webhook has to stay. The root transaction re-runs the closure
            // only before COMMIT, so every earlier attempt was rolled back.
            $last_profile = array_pop($built_profiles);
            foreach ($built_profiles as $built_profile)
                $built_profile->discardCreatedWebHooks();
            if (!is_null($last_profile)) {
                Log::warning(sprintf(
                    "PaymentGatewayProfileService::addPaymentProfile summit %s commit outcome unknown, kept the webhooks of the %s profile; reconcile them with the stored payment profiles.",
                    $summit->getId(),
                    $last_profile->getApplicationType()
                ));
            }
            throw $ex;
        } catch (\Throwable $ex) {
            foreach ($built_profiles as $built_profile)
                $built_profile->discardCreatedWebHooks();
            throw $ex;
        }

        if ($profile->isNew()) {
            Log::warning(sprintf(
                "PaymentGatewayProfileService::addPaymentProfile summit %s the transaction returned a %s profile that was not stored.",
                $summit->getId(),
                $profile->getApplicationType()
            ));
        }
        foreach ($built_profiles as $built_profile) {
            // the returned profile keeps its webhooks only when the transaction inserted it
            if ($built_profile !== $profile || $profile->isNew())
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
            Log::debug("PaymentGatewayProfileService::updatePaymentProfile", ["summit" => $summit->getId(), "profile_id" => $child_id, "payload" => PaymentGatewayProfile::toLogContext($payload)]);
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
