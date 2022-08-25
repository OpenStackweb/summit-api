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

use App\Http\Exceptions\HTTP400BadRequestException;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
/**
 * Trait GetAndValidationJsonPayload
 * @package App\Http\Controllers\Apis
 */
trait GetAndValidateJsonPayload
{
    /**
     * @param array $validation_rules
     * @param bool $validate_json_payload
     * @param array $messages
     * @return array
     * @throws HTTP400BadRequestException
     * @throws ValidationException
     */
    public function getJsonPayload(array $validation_rules = [], bool $validate_json_payload = false, array $messages = []): array{

        if ($validate_json_payload && !Request::isJson()){
           throw new HTTP400BadRequestException("Request has no JSON content.");
        }

        if(!Request::isJson()){
            return [];
        }

        $data    = Request::json();
        $payload = $data->all();
        // Creates a Validator instance and validates the data.
        $validation = Validator::make($payload, $validation_rules, $messages);

        if ($validation->fails()) {
            throw new ValidationException($validation->messages()->toArray());
        }

        return $payload;
    }
}