<?php namespace App\Services\Apis\Samsung;
/*
 * Copyright 2023 OpenStack Foundation
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


/**
 * Class CheckUserRequest
 * @package App\Services\Apis\Samsung
 */
final class CheckUserRequest extends AbstractPayload
{
    /**
     * @param string $userId
     * @param array $params
     */
    public function __construct(string $userId, array $params = []){

        parent::__construct($params);

        $this->payload = array_merge($this->payload , [
            PayloadParamNames::Type => RequestTypes::UserCheck,
            PayloadParamNames::UserId => trim($userId),
        ]);
    }

}