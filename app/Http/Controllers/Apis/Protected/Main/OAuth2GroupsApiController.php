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

use models\main\IGroupRepository;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2GroupsApiController
 * @package App\Http\Controllers
 */
final class OAuth2GroupsApiController extends OAuth2ProtectedController {
  use ParametrizedGetAll;

  /**
   * OAuth2MembersApiController constructor.
   * @param IGroupRepository $group_repository
   * @param IResourceServerContext $resource_server_context
   */
  public function __construct(
    IGroupRepository $group_repository,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($resource_server_context);
    $this->repository = $group_repository;
  }

  public function getAll() {
    return $this->_getAll(
      function () {
        return [
          "code" => ["=@", "==", "@@"],
          "title" => ["=@", "==", "@@"],
        ];
      },
      function () {
        return [
          "code" => "sometimes|string",
          "title" => "sometimes|string",
        ];
      },
      function () {
        return ["code", "title", "id"];
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
