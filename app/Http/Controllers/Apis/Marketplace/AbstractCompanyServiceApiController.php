<?php namespace App\Http\Controllers;
/**
 * Copyright 2017 OpenStack Foundation
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

use models\oauth2\IResourceServerContext;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;

/**
 * Class AbstractCompanyServiceApiController
 * @package App\Http\Controllers
 */
abstract class AbstractCompanyServiceApiController extends JsonController {
  /**
   * @var IBaseRepository
   */
  protected $repository;

  /**
   * @var IResourceServerContext
   */
  protected $resource_server_context;

  /**
   * @param IBaseRepository $repository
   * @param IResourceServerContext $resource_server_context
   */
  public function __construct(
    IBaseRepository $repository,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct();
    $this->repository = $repository;
    $this->resource_server_context = $resource_server_context;
  }

  protected function getResourceServerContext(): IResourceServerContext {
    return $this->resource_server_context;
  }

  protected function getRepository(): IBaseRepository {
    return $this->repository;
  }

  use ParametrizedGetAll;
  /**
   * @return mixed
   */
  public function getAll() {
    return $this->_getAll(
      function () {
        return [
          "name" => ["=@", "==", "@@"],
          "company" => ["=@", "==", "@@"],
        ];
      },
      function () {
        return [
          "name" => "sometimes|string",
          "company" => "sometimes|string",
        ];
      },
      function () {
        return ["name", "company", "id"];
      },
      function ($filter) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
    );
  }
}
