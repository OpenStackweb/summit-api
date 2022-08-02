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
use App\Http\Exceptions\HTTP403ForbiddenException;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use ModelSerializers\SerializerRegistry;
/**
 * Trait ParametrizedGetEntity
 * @package App\Http\Controllers
 */
trait ParametrizedGetEntity
{
    use BaseAPI;

    use RequestProcessor;

    /**
     * @param $id
     * @param callable $getEntityFn
     * @param mixed ...$args
     * @return mixed
     */
    public function _get($id, callable $getEntityFn, ...$args)
    {
        return $this->processRequest(function() use($id, $getEntityFn, $args){

            $entity = $getEntityFn($id, ...$args);
            if(is_null($entity))
                throw new EntityNotFoundException();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($entity)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }
}