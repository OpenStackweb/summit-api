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

use libs\utils\ITransactionService;
use models\summit\Summit;

/**
 * Class PromoCodeStrategyFactory
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class PromoCodeStrategyFactory
{
    /**
     * @var ITransactionService
     */
    protected $tx_service;

    /**
     * @var IPromoCodeGenerator
     */
    protected $code_generator;

    /**
     * PromoCodeStrategyFactory constructor.
     * @param ITransactionService $tx_service
     * @param IPromoCodeGenerator $code_generator
     */
    public function __construct(ITransactionService $tx_service,
                                IPromoCodeGenerator $code_generator)
    {
        $this->tx_service = $tx_service;
        $this->code_generator = $code_generator;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IPromoCodeStrategy
     */
    public function createStrategy(Summit $summit, array $payload): IPromoCodeStrategy
    {
        if (isset($payload["promo_code"]))
            return new ExistingMultiSpeakerPromoCodeStrategy($summit, $this->tx_service, $payload);

        return new AutomaticMultiSpeakerPromoCodeStrategy($summit, $this->tx_service, $this->code_generator, $payload);
    }
}