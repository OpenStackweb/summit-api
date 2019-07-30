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
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrinePaymentGatewayProfileRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="payment_profiles"
 *     )
 * })
 * @ORM\Table(name="PaymentGatewayProfile")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({"PaymentGatewayProfile" = "PaymentGatewayProfile",
 *     "StripePaymentProfile" = "StripePaymentProfile"
 * })
 * Class PaymentGatewayProfile
 * @package models\summit
 */
abstract class PaymentGatewayProfile extends SilverstripeBaseModel
{

    use SummitOwned;

    /**
     * @ORM\Column(name="IsActive", type="boolean")
     * @var bool
     */
    protected $active;

    /**
     * @ORM\Column(name="ApplicationType", type="string")
     * @var string
     */
    protected $application_type;

    /**
     * @ORM\Column(name="Provider", type="string")
     * @var string
     */
    protected $provider;

    /**
     * PaymentGatewayProfile constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->active           = false;
        $this->application_type = IPaymentConstants::ApplicationTypeRegistration;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate():void{
        $this->active = true;
    }

    public function disable():void{
        $this->active = false;
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

}