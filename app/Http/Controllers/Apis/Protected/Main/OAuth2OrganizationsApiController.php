<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Services\Model\IOrganizationService;
use models\main\IOrganizationRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2OrganizationsApiController
 * @package App\Http\Controllers
 */
final class OAuth2OrganizationsApiController extends OAuth2ProtectedController {
  /**
   * @var IOrganizationService
   */
  private $service;

  use ParametrizedGetAll;

  /**
   * OAuth2OrganizationsApiController constructor.
   * @param IOrganizationRepository $company_repository
   * @param IResourceServerContext $resource_server_context
   * @param IOrganizationService $service
   */
  public function __construct(
    IOrganizationRepository $company_repository,
    IResourceServerContext $resource_server_context,
    IOrganizationService $service,
  ) {
    parent::__construct($resource_server_context);
    $this->repository = $company_repository;
    $this->service = $service;
  }

  public function getAll() {
    return $this->_getAll(
      function () {
        return [
          "name" => ["=@", "==", "@@"],
        ];
      },
      function () {
        return [
          "name" => "sometimes|string",
        ];
      },
      function () {
        return ["name", "id"];
      },
      function ($filter) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
    );
  }

  use AddEntity;

  /**
   * @inheritDoc
   */
  function getAddValidationRules(array $payload): array {
    return [
      "name" => "required|string|max:255",
    ];
  }

  /**
   * @inheritDoc
   */
  protected function addEntity(array $payload): IEntity {
    return $this->service->addOrganization($payload);
  }
}
