<?php namespace App\Services\Apis;
/**
 * Copyright 2021 OpenStack Foundation
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
 * @from https://dashboard.mux.com/organizations/<organization-id>/settings/access-tokens
 * Class MuxCredentials
 * @package App\Services\Apis
 */
final class MuxCredentials
{
    /**
     * @var string
     */
    private $token_id;

    /**
     * @var string
     */
    private $token_secret;

    /**
     * MuxCredentials constructor.
     * @param string $token_id
     * @param string $token_secret
     */
    public function __construct(string $token_id, string $token_secret)
    {
        $this->token_id = $token_id;
        $this->token_secret = $token_secret;
    }

    /**
     * @return string
     */
    public function getTokenId(): string
    {
        return $this->token_id;
    }

    /**
     * @return string
     */
    public function getTokenSecret(): string
    {
        return $this->token_secret;
    }
}