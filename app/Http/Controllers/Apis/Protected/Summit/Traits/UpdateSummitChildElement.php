<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\Summit;
use models\utils\IEntity;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Trait UpdateSummitChildElement
 * @package App\Http\Controllers
 */
trait UpdateSummitChildElement
{
    use BaseSummitAPI;

    protected function updateSerializerType():string{
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @param array $payload
     * @return array
     */
    abstract function getUpdateValidationRules(array $payload): array;

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    abstract protected function updateChild(Summit $summit,int $child_id, array $payload):IEntity;

    /**
     * @param $summit_id
     * @param $child_id
     * @return mixed
     */
    public function update($summit_id, $child_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $this->getUpdateValidationRules($payload));

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $child = $this->updateChild($summit, $child_id, $payload);

            $fields = Request::input('fields', '');
            $relations = Request::input('relations', '');

            $relations = !empty($relations) ? explode(',', $relations) : [];
            $fields = !empty($fields) ? explode(',', $fields) : [];

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $child,
                $this->updateSerializerType()
            )->serialize(
                Request::input('expand', ''),
                $fields,
                $relations
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}