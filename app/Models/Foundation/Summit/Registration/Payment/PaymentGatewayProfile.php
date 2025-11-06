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
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'PaymentGatewayProfile')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrinePaymentGatewayProfileRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'payment_profiles')])]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['PaymentGatewayProfile' => 'PaymentGatewayProfile', 'StripePaymentProfile' => 'StripePaymentProfile', 'LawPayPaymentProfile' => 'LawPayPaymentProfile'])] // Class PaymentGatewayProfile
abstract class PaymentGatewayProfile extends SilverstripeBaseModel
{

    use SummitOwned;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsActive', type: 'boolean')]
    protected $active;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ExternalId', type: 'string')]
    protected $external_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ApplicationType', type: 'string')]
    protected $application_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Provider', type: 'string')]
    protected $provider;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsTestModeEnabled', type: 'boolean')]
    protected $test_mode_enabled;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LiveSecretKey', type: 'string')]
    protected $live_secret_key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LivePublishableKey', type: 'string')]
    protected $live_publishable_key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TestSecretKey', type: 'string')]
    protected $test_secret_key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TestPublishableKey', type: 'string')]
    protected $test_publishable_key;

    /**
     * PaymentGatewayProfile constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->active = false;
        $this->test_mode_enabled = true;
        $this->application_type = IPaymentConstants::ApplicationTypeRegistration;
        $this->live_publishable_key = '';
        $this->live_secret_key = '';
        $this->test_publishable_key = '';
        $this->test_secret_key = '';
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @throws ValidationException
     */
    public function activate(): void
    {
        if (!$this->hasSecretKey()) {
            throw new ValidationException("You can not activate a profile without a secret key set.");
        }

        if (!$this->hasPublicKey()) {
            throw new ValidationException("You can not activate a profile without a published key set.");
        }

        Log::debug("PaymentGatewayProfile::activate");

        $this->active = true;

        $this->buildWebHook();
    }

    public function disable():void{
        Log::debug("PaymentGatewayProfile::disable");
        $this->active = false;
        $this->clearWebHooks();
    }

    /**
     * @return string
     */
    public function getApplicationType(): ?string
    {
        return $this->application_type;
    }

    /**
     * @param string $application_type
     * @throws ValidationException
     */
    public function setApplicationType(string $application_type): void
    {
        if(!in_array($application_type, IPaymentConstants::ValidApplicationTypes))
            throw new ValidationException(sprintf("Application Type %s is not valid.", $application_type));

        $this->application_type = $application_type;
    }

    /**
     * @return string
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * @return IPaymentGatewayAPI
     */
    abstract public function buildPaymentGatewayApi():IPaymentGatewayAPI;

    /**
     * @return bool
     */
    public function isTestModeEnabled(): bool
    {
        return $this->test_mode_enabled;
    }

    /**
     * @return string
     */
    public function getLiveSecretKey(): ?string
    {
        return $this->live_secret_key;
    }

    /**
     * @return string
     */
    public function getLivePublishableKey(): ?string
    {
        return $this->live_publishable_key;
    }

    /**
     * @return bool
     */
    public function hasSecretKey(): bool
    {
        if ($this->test_mode_enabled) {
            return !empty($this->test_secret_key);
        }
        return !empty($this->live_secret_key);
    }

    /**
     * @return bool
     */
    public function hasPublicKey(): bool
    {
        if ($this->test_mode_enabled) {
            return !empty($this->test_publishable_key);
        }
        return !empty($this->live_publishable_key);
    }


    /**
     * @return array
     */
    protected function createConfiguration(): array
    {
        if ($this->test_mode_enabled) {
            return $this->createTestConfiguration();
        }
        return $this->createLiveConfiguration();
    }

    /**
     * @return array
     */
    protected function createTestConfiguration(): array
    {
        $params = [
            'secret_key' => $this->test_secret_key,
        ];

        return $params;
    }

    /**
     * @return array
     */
    protected function createLiveConfiguration(): array
    {
        $params = [
            'secret_key' => $this->live_secret_key,
        ];

        return $params;
    }

    /**
     * @param array $keys
     */
    public function setLiveKeys(array $keys): void
    {
        $this->live_publishable_key = $keys['publishable_key'];
        $this->live_secret_key = $keys['secret_key'];
    }

    /**
     * @param string $live_secret_key
     */
    public function setLiveSecretKey(string $live_secret_key): void
    {
        $this->live_secret_key = $live_secret_key;
    }

    /**
     * @param string $live_publishable_key
     */
    public function setLivePublishableKey(string $live_publishable_key): void
    {
        $this->live_publishable_key = $live_publishable_key;
    }

    /**
     * @param string $test_secret_key
     */
    public function setTestSecretKey(string $test_secret_key): void
    {
        $this->test_secret_key = $test_secret_key;
    }

    /**
     * @param string $test_publishable_key
     */
    public function setTestPublishableKey(string $test_publishable_key): void
    {
        $this->test_publishable_key = $test_publishable_key;
    }

    /**
     * @return string
     */
    public function getTestSecretKey(): ?string
    {
        return $this->test_secret_key;
    }

    /**
     * @return string
     */
    public function getTestPublishableKey(): ?string
    {
        return $this->test_publishable_key;
    }

    /**
     * @param array $keys
     */
    public function setTestKeys(array $keys): void
    {
        $this->test_publishable_key = $keys['publishable_key'];
        $this->test_secret_key = $keys['secret_key'];
    }

    public function setLiveMode(): void
    {
        $this->test_mode_enabled = false;
        $this->buildWebHook();
    }

    public function setTestMode(): void
    {
        $this->test_mode_enabled = true;
        $this->buildWebHook();
    }

    abstract public function buildWebHook(): void;

    abstract protected function clearWebHooks():void;

    public function setExternalId(string $external_id): void{
        $this->external_id = $external_id;
    }

    public function getExternalId(): ?string{
        return $this->external_id;
    }
}
