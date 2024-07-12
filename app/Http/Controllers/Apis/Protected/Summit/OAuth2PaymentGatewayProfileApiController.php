<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Summit\Repositories\IPaymentGatewayProfileRepository;
use App\Services\Model\IPaymentGatewayProfileService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2PaymentGatewayProfileApiController
 * @package App\Http\Controller
 */
final class OAuth2PaymentGatewayProfileApiController extends OAuth2ProtectedController {
  /**
   * @var IPaymentGatewayProfileService
   */
  private $service;

  /**
   * @var ISummitRepository
   */
  private $summit_repository;

  /**
   * OAuth2PaymentGatewayProfileApiController constructor.
   * @param IPaymentGatewayProfileRepository $repository
   * @param ISummitRepository $summit_repository
   * @param IPaymentGatewayProfileService $service
   * @param IResourceServerContext $resource_server_context
   */
  public function __construct(
    IPaymentGatewayProfileRepository $repository,
    ISummitRepository $summit_repository,
    IPaymentGatewayProfileService $service,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($resource_server_context);
    $this->repository = $repository;
    $this->summit_repository = $summit_repository;
    $this->service = $service;
  }

  use GetAllBySummit;

  use GetSummitChildElementById;

  use AddSummitChildElement;

  use UpdateSummitChildElement;

  use DeleteSummitChildElement;

  /**
   * @return ISummitRepository
   */
  protected function getSummitRepository(): ISummitRepository {
    return $this->summit_repository;
  }

  /**
   * @inheritDoc
   */
  protected function addChild(Summit $summit, array $payload): IEntity {
    return $this->service->addPaymentProfile($summit, $payload);
  }

  /**
   * @inheritDoc
   */
  function getAddValidationRules(array $payload): array {
    return PaymentGatewayProfileValidationRulesFactory::build($payload, false);
  }

  /**
   * @inheritDoc
   */
  protected function deleteChild(Summit $summit, $child_id): void {
    $this->service->deletePaymentProfile($summit, $child_id);
  }

  /**
   * @inheritDoc
   */
  protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity {
    return $summit->getPaymentProfileById($child_id);
  }

  /**
   * @inheritDoc
   */
  function getUpdateValidationRules(array $payload): array {
    return PaymentGatewayProfileValidationRulesFactory::build($payload, true);
  }

  /**
   * @inheritDoc
   */
  protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity {
    return $this->service->updatePaymentProfile($summit, $child_id, $payload);
  }

  /**
   * @return array
   */
  protected function getFilterRules(): array {
    return [
      "application_type" => ["=@", "=="],
      "active" => ["=="],
    ];
  }

  /**
   * @return array
   */
  protected function getFilterValidatorRules(): array {
    return [
      "application_type" => "sometimes|required|string",
      "active" => "sometimes|required|boolean",
    ];
  }
  /**
   * @return array
   */
  protected function getOrderRules(): array {
    return ["id", "application_type"];
  }

  protected function serializerType(): string {
    return SerializerRegistry::SerializerType_Private;
  }

  protected function addSerializerType(): string {
    return SerializerRegistry::SerializerType_Private;
  }

  protected function updateSerializerType(): string {
    return SerializerRegistry::SerializerType_Private;
  }

  public function getChildSerializer() {
    return SerializerRegistry::SerializerType_Private;
  }
}
