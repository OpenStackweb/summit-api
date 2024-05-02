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
use Illuminate\Support\Facades\Log;

/**
 * Class EncryptedRequest
 * @package App\Services\Apis\Samsung
 */
final class EncryptedPayload extends AbstractPayload
{
    /**
     * @param string $key
     * @param AbstractPayload $request
     * @throws \Exception
     */
    public function __construct(string $key, AbstractPayload $request){

        $data =(string)$request;
        Log::debug(sprintf("EncryptedPayload::constructor request %s.", $data));
        $enc = AES256GCM::encrypt($key, $data);
        if($enc->hasError())
            throw new \Exception($enc->getErrorMessage());

        $this->payload = [
            PayloadParamNames::Data => $enc->getData()
        ];
    }
}