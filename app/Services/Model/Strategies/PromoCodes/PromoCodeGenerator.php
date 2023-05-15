<?php namespace App\Services\Model\Strategies\PromoCodes;

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
use models\summit\Summit;
use Zend\Math\Rand;

/**
 * Class PromoCodeGenerator
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class PromoCodeGenerator implements IPromoCodeGenerator
{
    const VsChar = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @param ICacheService $cache_service
     */
    public function __construct(ICacheService $cache_service)
    {
        $this->cache_service = $cache_service;
    }

    private function generateValue(int $length): string
    {
        // calculate value
        // entropy(SHANNON FANO Approx) len * log(count(VsChar))/log(2) = bits of entropy
        return Rand::getString($length, self::VsChar);
    }

    /**
     * @inheritDoc
     */
    public function generate(Summit $summit, int $length): string
    {
        do {
            $key = sprintf("%s_%s", $summit->getName(), $this->generateValue($length));
        } while(!$this->cache_service->addSingleValue($key, $key));

        return strtoupper($key);
    }
}