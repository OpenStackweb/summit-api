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
use App\Utils\AES256GCM;

/**
 * Class DecryptedResponse
 * @package App\Services\Apis\Samsung
 */
final class DecryptedSingleResponse extends AbstractPayload
{
    /**
     * @param string $key
     * @param string $content
     * @param array $params
     * @throws EmptyResponse
     * @throws InvalidResponse
     */
    public function __construct(string $key, string $content, array $params){

        parent::__construct($params);
        $response = json_decode($content, true);
        if(is_array($response) && !count($response))
            throw new EmptyResponse("response not found");

        if(!isset($response['data']))
            throw new InvalidResponse(sprintf("missing data field on response %s", $content));

        $dec = AES256GCM::decrypt($key, $response['data']);
        if($dec->hasError())
            throw new InvalidResponse($dec->getErrorMessage());

        $list = json_decode($dec->getData(), true);
        if(!is_array($list))
            throw new InvalidResponse(sprintf("invalid data field on response %s", $content));
        $this->payload = count($list) == 1 ? $list[0] : $list;

        if(count($this->payload) == 0)
            throw new EmptyResponse("response not found");
    }


    public function getPayload(): array
    {
        return SamsungRecordSerializer::serialize($this->payload, $this->params);
    }

}