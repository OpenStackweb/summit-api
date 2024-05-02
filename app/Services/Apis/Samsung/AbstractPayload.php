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
 * Class AbstractPayloadRequest
 * @package App\Services\Apis\Samsung
 */
abstract class AbstractPayload
{
    protected $payload = [];

    protected $params = [];

    /**
     * @param array $params
     */
    function __construct(array $params)
    {
        if(!isset($params[PayloadParamNames::Forum]))
            throw new \InvalidArgumentException("missing forum param");

        if(!isset($params[PayloadParamNames::Region]))
            throw new \InvalidArgumentException("missing region param");

        if(!isset($params[PayloadParamNames::GBM]))
            throw new \InvalidArgumentException("missing gbm param");

        if(!isset($params[PayloadParamNames::Year]))
            throw new \InvalidArgumentException("missing year param");

        $this->params = $params;

        $this->payload = [
            PayloadParamNames::Forum => $params[PayloadParamNames::Forum],
            PayloadParamNames::Region => $params[PayloadParamNames::Region],
            PayloadParamNames::GBM => $params[PayloadParamNames::GBM],
            PayloadParamNames::Year => $params[PayloadParamNames::Year],
        ];
    }

    public function __toString()
    {
        return json_encode($this->payload, JSON_UNESCAPED_UNICODE);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}