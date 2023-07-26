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

use Zend\Math\Rand;

/**
 * Class EncryptionKeysGenerator
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class EncryptionKeysGenerator implements IEncryptionKeysGenerator
{
    const VsChar = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    const Length = 32;

    /**
     * @var int
     */
    private $length;

    /**
     * @param int $length
     */
    public function __construct(int $length = self::Length)
    {
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
        return strtoupper($this->generateValue());
    }
}