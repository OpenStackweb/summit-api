<?php namespace models\summit;
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
use App\Services\Apis\PaymentGateways\LawPayApi;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use App\Services\Apis\IPaymentGatewayAPI;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="LawPayPaymentProfile")
 * @ORM\HasLifecycleCallbacks
 * Class LawPayPaymentProfile
 * @package models\summit
 */
class LawPayPaymentProfile extends PaymentGatewayProfile
{

    /**
     * @ORM\Column(name="MerchantAccountId", type="string")
     * @var string
     */
    protected $merchant_account_id;

    /**
     * @return IPaymentGatewayAPI
     */
    public function buildPaymentGatewayApi(): IPaymentGatewayAPI
    {
        return new LawPayApi($this->createConfiguration());
    }

    /**
     * @throws ValidationException
     */
    public function buildWebHook(): void
    {
        try{
           $this->buildPaymentGatewayApi()->createWebHook(
               action('PaymentGatewayWebHookController@confirm', [
                   'id' => $this->summit->getId(),
                   'application_name' => $this->getApplicationType()
               ])
           );
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw new ValidationException("Can not create the LawPay Webhook, please review your provided credentials.");
        }
    }

    /**
     * @throws ValidationException
     */
    protected function clearWebHooks(): void
    {
        try{
            $this->buildPaymentGatewayApi()->clearWebHooks();
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw new ValidationException("Can not delete the LawPay Webhook, please review your provided credentials.");
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->merchant_account_id = null;
        $this->provider = IPaymentConstants::ProviderLawPay;
    }

    /**
     * @return array
     */
    protected function createTestConfiguration(): array
    {
        $params = parent::createTestConfiguration();
        return array_merge($params, [
           'public_key' => $this->test_publishable_key,
           'account_id' => $this->merchant_account_id,
           'test_mode_enabled' => true,
        ]);
    }

    /**
     * @return array
     */
    protected function createLiveConfiguration(): array
    {
        $params = parent::createLiveConfiguration();
        return array_merge($params, [
            'public_key' => $this->live_publishable_key,
            'account_id' => $this->merchant_account_id,
            'test_mode_enabled' => false,
        ]);
    }


    /**
     * @return string|null
     */
    public function getMerchantAccountId(): ?string
    {
        return $this->merchant_account_id;
    }

    /**
     * @param string|null $merchant_account_id
     */
    public function setMerchantAccountId(?string $merchant_account_id): void
    {
        $this->merchant_account_id = $merchant_account_id;
    }
}