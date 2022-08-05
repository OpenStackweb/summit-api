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
 * Trait ParametrizedAddEntity
 * @package App\Http\Controllers
 */
trait ParametrizedAddEntity
{
    use BaseAPI;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    public function _add(
        callable $getAddValidationRulesFn,
        callable $addEntityFn,
                 ...$args
    )
    {
        return $this->processRequest(function () use ($getAddValidationRulesFn, $addEntityFn, $args) {

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $payload = $this->getJsonPayload($getAddValidationRulesFn($data->all()));

            $entity = $addEntityFn($payload, ...$args);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($entity)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}