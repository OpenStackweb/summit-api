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

use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedResponse;
use App\Utils\AES256GCM;
use Illuminate\Support\Facades\Log;

/**
 * Class DecryptedListResponse
 * @package App\Services\Apis\Samsung
 */
final class DecryptedListResponse
    extends AbstractPayload
implements IExternalRegistrationFeedResponse
{
    private $position = 0;
    /**
     * @param string $key
     * @param string $content
     * @param array $params
     * @throws EmptyResponse
     * @throws InvalidResponse
     */
    public function __construct(string $key, string $content, array $params = []){

        parent::__construct($params);

        if(empty($content)){
            Log::warning("DecryptedListResponse::constructor empty content.");
            throw new EmptyResponse("response not found.");
        }

        $this->position  = 0;

        $response = json_decode($content, true);
        if(is_array($response) && !count($response))
            throw new EmptyResponse("response not found.");

        Log::debug(sprintf("DecryptedListResponse::constructor response %s.", json_encode($response)));

        if(!isset($response['data']))
            throw new InvalidResponse(sprintf("missing data field on response %s.", $content));

        if(empty($response['data']))
            throw new EmptyResponse("response not found.");

        $dec = AES256GCM::decrypt($key, $response['data']);
        if($dec->hasError()) {
            Log::warning(sprintf("DecryptedListResponse::constructor error %s.", $dec->getErrorMessage()));
            throw new InvalidResponse($dec->getErrorMessage());
        }

        $data = $dec->getData();

        Log::debug(sprintf("DecryptedListResponse::constructor data %s.", $data));

        $list = json_decode($data, true);
        if(!is_array($list))
            throw new InvalidResponse(sprintf("invalid data field on response %s", $content));

        if(count($list) == 0)
            throw new EmptyResponse("response not found");

        $this->payload = $list;
    }

    public function current()
    {
        return SamsungRecordSerializer::serialize
        (
            $this->payload[$this->position],
            $this->params
        );
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return $this->position < count($this->payload);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function hasData(): bool
    {
        return count($this->payload) > 0;
    }

    public function hasMoreItems(): bool
    {
         return false;
    }

    public function pageCount(): int
    {
        return count($this->payload);
    }
}