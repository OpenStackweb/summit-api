<?php namespace App\Services\Utils\Security;

/**
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

use libs\utils\ICacheService;
use Laminas\Math\Rand;

/**
 * Class EncryptionKeysGenerator
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class EncryptionAES256KeysGenerator implements IEncryptionAES256KeysGenerator
{
    const VsChar = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    //AES256 default key length is 32 bytes
    const Length = 32;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @var int
     */
    private $length;

    /**
     * @param ICacheService $cache_service
     * @param int $length
     */
    public function __construct(ICacheService $cache_service, int $length = self::Length)
    {
        $this->cache_service = $cache_service;
        $this->length = $length;
    }

    private function generateValue(): string
    {
        // calculate value
        // entropy(SHANNON FANO Approx) len * log(count(VsChar))/log(2) = bits of entropy
        return Rand::getString($this->length, self::VsChar);
    }

    /**
     * @inheritDoc
     */
    public function generate(): string
    {
        do {
            $key = strtoupper($this->generateValue());
        } while(!$this->cache_service->addSingleValue($key, $key));

        return $key;
    }
}