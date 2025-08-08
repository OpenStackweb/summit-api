<?php namespace App\Models\Utils\Traits;
/*
 * Copyright 2025 OpenStack Foundation
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


use Illuminate\Support\Facades\Log;
use models\utils\RandomGenerator;


trait HasTokenTrait
{
    /**
     * transient variable
     * @var string
     */
    protected string $token;

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    protected abstract function generateTokenSeed():string;
    /**
     * @return string
     */
    public function generateConfirmationToken(): string
    {
        $generator = new RandomGenerator();
        // build seed
        $seed = $this->generateTokenSeed();
        $seed .= $generator->randomToken();
        // stores token in memory
        $this->token = md5($seed);
        $this->hash = self::HashConfirmationToken($this->token);
        Log::debug(sprintf("HasTokenTrait::generateConfirmationToken id %s token %s hash %s", $this->id, $this->token, $this->hash));
        return $this->token;
    }

    public static function HashConfirmationToken(string $token): string
    {
        return md5($token);
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }
}