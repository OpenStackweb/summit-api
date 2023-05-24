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
 * Class CheckEmailRequest
 * @package App\Services\Apis\Samsung
 */
final class CheckEmailRequest extends AbstractPayload
{
    /**
     * @param string $email
     * @param string $forum
     * @param string $region
     */
    public function __construct(string $email, string $forum, string $region){
        $this->payload = [
            PayloadParamNames::Type => RequestTypes::EmailCheck,
            PayloadParamNames::Email => trim($email),
            PayloadParamNames::Forum => $forum,
            PayloadParamNames::Region => $region
        ];
    }
}