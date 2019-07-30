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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Trait GetEntity
 * @package App\Http\Controllers
 */
trait GetEntity
{
    use BaseAPI;

    /**
     * @param int $id
     * @return IEntity
     */
    abstract protected function getEntity(int $id): IEntity;

    /**
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        try {
            $entity = $this->getEntity($id);
            $fields = Request::input('fields', '');
            $relations = Request::input('relations', '');

            $relations = !empty($relations) ? explode(',', $relations) : [];
            $fields = !empty($fields) ? explode(',', $fields) : [];

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($entity)->serialize(
                Request::input('expand', ''),
                $fields,
                $relations
            ));

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}