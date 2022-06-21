<?php namespace models\summit;
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
use App\Services\Apis\IPaymentGatewayAPI;
use App\Services\Apis\PaymentGateways\StripeApi;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="StripePaymentProfile")
 * @ORM\HasLifecycleCallbacks
 * Class StripePaymentProfile
 * @package models\summit
 */
class StripePaymentProfile extends PaymentGatewayProfile
{

    /**
     * @ORM\Column(name="LiveWebHookId", type="string")
     * @var string
     */
    protected $live_webhook_id;

    /**
     * @ORM\Column(name="LiveWebHookSecretKey", type="string")
     * @var string
     */
    protected $live_webhook_secret_key;

    /**
     * @ORM\Column(name="TestWebHookId", type="string")
     * @var string
     */
    protected $test_webhook_id;

    /**
     * @ORM\Column(name="TestWebHookSecretKey", type="string")
     * @var string
     */
    protected $test_webhook_secret_key;

    /**
     * @ORM\Column(name="SendEmailReceipt", type="boolean")
     * @var bool
     */
    protected $send_email_receipt;

    /**
     * StripePaymentProfile constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->send_email_receipt = false;
        $this->provider = IPaymentConstants::ProviderStripe;
        $this->live_webhook_id = '';
        $this->live_webhook_secret_key = '';
        $this->test_webhook_id = '';
        $this->test_webhook_secret_key = '';
    }

    /**
     * @return string
     */
    public function getLiveWebhookId(): ?string
    {
        return $this->live_webhook_id;
    }

    /**
     * @return string
     */
    public function getLiveWebhookSecretKey(): ?string
    {
        return $this->live_webhook_secret_key;
    }

    /**
     * @return string
     */
    public function getTestWebhookId(): ?string
    {
        return $this->test_webhook_id;
    }

    /**
     * @return string
     */
    public function getTestWebhookSecretKey(): ?string
    {
        return $this->test_webhook_secret_key;
    }

    /**
     * @param array $webhook_data
     */
    public function setLiveWebHookData(array $webhook_data): void
    {
        $this->live_webhook_id = $webhook_data['id'];
        $this->live_webhook_secret_key = $webhook_data['secret_key'];
    }

    /**
     * @param array $webhook_data
     */
    public function setTestWebHookData(array $webhook_data): void
    {
        $this->test_webhook_id = $webhook_data['id'];
        $this->test_webhook_secret_key = $webhook_data['secret_key'];
    }

    /**
     * @return array
     */
    protected function createTestConfiguration(): array
    {
        $params = parent::createTestConfiguration();
        $params['send_email_receipt' ] = $this->send_email_receipt;

        if (!empty($this->test_webhook_secret_key)) {
            $params['webhook_secret_key'] = $this->test_webhook_secret_key;
        }

        return $params;
    }

    /**
     * @return array
     */
    protected function createLiveConfiguration(): array
    {
        $params = parent::createLiveConfiguration();
        $params['send_email_receipt' ] = $this->send_email_receipt;

        if (!empty($this->live_webhook_secret_key)) {
            $params['webhook_secret_key'] = $this->live_webhook_secret_key;
        }
        return $params;
    }

    /**
     * @return bool
     */
    public function existsWebHook(): bool
    {
        if ($this->test_mode_enabled) {
            return !empty($this->test_webhook_id) || !empty($this->test_webhook_secret_key);
        }
        return !empty($this->live_webhook_id) || !empty($this->live_webhook_secret_key);
    }

    /**
     * @return bool
     */
    public function existsWebHookTest(): bool
    {
        return !empty($this->test_webhook_id);
    }

    /**
     * @return bool
     */
    public function existsWebHookLive(): bool
    {
        return !empty($this->live_webhook_id);
    }

    /**
     * @param array $info
     */
    private function setWebHookInfo(array $info): void
    {
        if ($this->test_mode_enabled) {
            $this->test_webhook_secret_key = $info['secret'];
            $this->test_webhook_id = $info['id'];
            return;
        }
        $this->live_webhook_secret_key = $info['secret'];
        $this->live_webhook_id = $info['id'];
    }

    /**
     * @return null|string
     */
    public function getWebHookSecretKey(): ?string
    {
        if ($this->test_mode_enabled) {
            return $this->test_webhook_secret_key;
        }
        return $this->live_webhook_secret_key;
    }

    /**
     * @return null|string
     */
    public function getWebHookId(): ?string
    {
        if ($this->test_mode_enabled) {
            return $this->test_webhook_id;
        }
        return $this->live_webhook_id;
    }

    public function buildWebHook(): void
    {
        try {
            if (!$this->existsWebHook() && $this->hasSecretKey() && $this->hasSummit()) {
                $api = new StripeApi($this->createConfiguration());
                // create it
                $info = $api->createWebHook(action('PaymentGatewayWebHookController@confirm', [
                    'id' => $this->summit->getId(),
                    'application_name' => $this->getApplicationType()
                ]));
                // and set web hook info
                $this->setWebHookInfo($info);
            }
        } catch (\Exception $ex) {
            Log::error($ex);
            throw new ValidationException("Can not create the Stripe Webhook, please review your provided credentials.");
        }
    }

    /**
     * @return IPaymentGatewayAPI
     */
    public function buildPaymentGatewayApi(): IPaymentGatewayAPI
    {
        $api = new StripeApi($this->createConfiguration());
        if ($this->existsWebHook()) {
            $api->setWebHookSecretKey($this->getWebHookSecretKey());
        }
        return $api;
    }

    protected function clearTestWebHook(): void
    {
        try {
            Log::debug("StripePaymentProfile::clearTestWebHook");
            if ($this->existsWebHookTest()) {
                $api = new StripeApi($this->createTestConfiguration());
                // delete it
                Log::debug(sprintf("StripePaymentProfile::clearTestWebHook deleting webhook %s", $this->getTestWebhookId()));
                $api->deleteWebHookById($this->getTestWebhookId());
                Log::debug(sprintf("StripePaymentProfile::clearTestWebHook webhook %s deleted", $this->getTestWebhookId()));
                $this->test_webhook_secret_key =  $this->test_webhook_id = '';
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    protected function clearLiveWebHook(): void
    {
        try {
            Log::debug("StripePaymentProfile::clearLiveWebHook");
            if ($this->existsWebHookLive()) {
                $api = new StripeApi($this->createLiveConfiguration());
                // delete it
                Log::debug(sprintf("StripePaymentProfile::clearLiveWebHook deleting webhook %s", $this->getTestWebhookId()));
                $api->deleteWebHookById($this->getLiveWebhookId());
                Log::debug(sprintf("StripePaymentProfile::clearLiveWebHook webhook %s deleted", $this->getTestWebhookId()));
                $this->live_webhook_secret_key =  $this->live_webhook_id = '';

            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    protected function clearWebHooks(): void
    {
        Log::debug("StripePaymentProfile::clearWebHooks");
        $this->clearLiveWebHook();
        $this->clearTestWebHook();
    }

    /**
     * @ORM\PreRemove
     */
    public function deleting($args)
    {
        // remove web hooks
        $this->clearWebHooks();
    }

    /**
     * @param string $live_webhook_secret_key
     */
    public function setLiveWebhookSecretKey(string $live_webhook_secret_key): void
    {
        Log::debug(sprintf("StripePaymentProfile::setLiveWebhookSecretKey %s", $live_webhook_secret_key));
        $this->live_webhook_secret_key = $live_webhook_secret_key;
    }

    /**
     * @param string $test_webhook_secret_key
     */
    public function setTestWebhookSecretKey(string $test_webhook_secret_key): void
    {
        Log::debug(sprintf("StripePaymentProfile::setTestWebhookSecretKey %s", $test_webhook_secret_key));
        $this->test_webhook_secret_key = $test_webhook_secret_key;
    }

    /**
     * @return bool
     */
    public function isSendEmailReceipt(): bool
    {
        return $this->send_email_receipt;
    }

    /**
     * @param bool $send_email_receipt
     */
    public function setSendEmailReceipt(bool $send_email_receipt): void
    {
        $this->send_email_receipt = $send_email_receipt;
    }

}