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

use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Request;
use ModelSerializers\SerializerRegistry;

/**
 * Trait ParametrizedUpdateEntity
 * @package App\Http\Controllers
 */
trait ParametrizedUpdateEntity {
  use BaseAPI;

  use RequestProcessor;

  use GetAndValidateJsonPayload;

  /**
   * @param int $id
   * @param callable $getUpdateValidationRulesFn
   * @param callable $updateEntityFn
   * @param mixed ...$args
   * @return mixed
   */
  public function _update(
    int $id,
    callable $getUpdateValidationRulesFn,
    callable $updateEntityFn,
    ...$args,
  ) {
    return $this->processRequest(function () use (
      $id,
      $getUpdateValidationRulesFn,
      $updateEntityFn,
      $args,
    ) {
      if (!Request::isJson()) {
        return $this->error400();
      }
      $data = Request::json();
      $payload = $this->getJsonPayload($getUpdateValidationRulesFn($data->all()));
      $entity = $updateEntityFn($id, $payload, ...$args);

      return $this->updated(
        SerializerRegistry::getInstance()
          ->getSerializer($entity)
          ->serialize(
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
          ),
      );
    });
  }
}
