<?php namespace App\Services;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Factories\SummitRefundPolicyTypeFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitRefundPolicyTypeService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitRefundPolicyType;
/**
 * Class SummitRefundPolicyTypeService
 * @package App\Services\Model\Imp
 */
final class SummitRefundPolicyTypeService
    extends AbstractService
    implements ISummitRefundPolicyTypeService
{

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitRefundPolicyType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPolicy(Summit $summit, array $payload): SummitRefundPolicyType
    {
        return $this->tx_service->transaction(function() use($summit, $payload){
            $name = trim($payload['name']);
            $days = intval($payload['until_x_days_before_event_starts']);
            $former_policy = $summit->getRefundPolicyByName($name);
            if(!is_null($former_policy))
                throw new ValidationException(sprintf("%s refund policy name already exists", $name));
            $former_policy = $summit->getRefundPolicyByDays($days);
            if(!is_null($former_policy))
                throw new ValidationException(sprintf("refund policy for %s days already exists", $days));

            $policy = SummitRefundPolicyTypeFactory::build($payload);

            $summit->addRefundPolicy($policy);

            return $policy;
        });
    }

    /**
     * @param Summit $summit
     * @param int $policy_id
     * @param array $payload
     * @return SummitRefundPolicyType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updatePolicy(Summit $summit, int $policy_id, array $payload): SummitRefundPolicyType
    {
        return $this->tx_service->transaction(function() use($summit, $policy_id, $payload){
            $policy = $summit->getRefundPolicyById($policy_id);
            if(is_null($policy))
                throw new EntityNotFoundException("policy not found");

            if(isset($payload['name'])) {
                $name = trim($payload['name']);
                $former_policy = $summit->getRefundPolicyByName($name);
                if (!is_null($former_policy) && $former_policy->getId() != $policy_id)
                    throw new ValidationException(sprintf("%s refund policy name already exists", $name));
            }

            if(isset($payload['until_x_days_before_event_starts'])) {
                $days = intval($payload['until_x_days_before_event_starts']);
                $former_policy = $summit->getRefundPolicyByDays($days);
                if (!is_null($former_policy) && $former_policy->getId() != $policy_id)
                    throw new ValidationException(sprintf("refund policy for %s days already exists", $days));
            }

            return SummitRefundPolicyTypeFactory::populate($policy, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $policy_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePolicy(Summit $summit, int $policy_id): void
    {
        $this->tx_service->transaction(function() use($summit, $policy_id){
            $policy = $summit->getRefundPolicyById($policy_id);
            if(is_null($policy))
                throw new EntityNotFoundException("policy not found");

            $summit->removeRefundPolicy($policy);
        });
    }
}