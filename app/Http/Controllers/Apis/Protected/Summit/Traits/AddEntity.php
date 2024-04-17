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
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Trait AddEntity
 * @package App\Http\Controllers
 */
trait AddEntity
{
    use BaseAPI;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    /**
     * @param array $payload
     * @return array
     */
    abstract function getAddValidationRules(array $payload): array;

    /**
     * @param array $payload
     * @return IEntity
     */
    abstract protected function addEntity(array $payload): IEntity;


    protected function addEntitySerializerType(){
        return SerializerRegistry::SerializerType_Public;
    }
    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function add()
    {
        return $this->processRequest(function () {

            $payload = $this->getJsonPayload($this->getAddValidationRules(Request::all()));

            $entity = $this->addEntity($payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($entity, $this->addEntitySerializerType())->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}