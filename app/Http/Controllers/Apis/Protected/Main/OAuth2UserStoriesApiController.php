<?php namespace App\Http\Controllers;
/**
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Main\Repositories\IUserStoryRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2UserStoriesApiController
 * @package App\Http\Controllers
 */
final class OAuth2UserStoriesApiController extends OAuth2ProtectedController {
  use ParametrizedGetAll;

  /**
   * OAuth2UserStoriesApiController constructor.
   * @param IUserStoryRepository $repository
   * @param IResourceServerContext $resource_server_context
   */
  public function __construct(
    IUserStoryRepository $repository,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($resource_server_context);
    $this->repository = $repository;
  }

  /**
   * @return mixed
   */
  public function getAllUserStories() {
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
        return $this->getEntitySerializerType();
      },
    );
  }

  protected function getEntitySerializerType(): string {
    $currentUser = $this->resource_server_context->getCurrentUser();
    return !is_null($currentUser)
      ? SerializerRegistry::SerializerType_Private
      : SerializerRegistry::SerializerType_Public;
  }
}
