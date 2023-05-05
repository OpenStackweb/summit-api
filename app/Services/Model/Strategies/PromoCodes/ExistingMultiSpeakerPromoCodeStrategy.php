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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationSpeaker;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class ExistingSpeakersPromoCodeStrategy
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class ExistingMultiSpeakerPromoCodeStrategy implements IPromoCodeStrategy
{
    /**
     * @var ITransactionService
     */
    protected $tx_service;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $data;

    /**
     * PromoCodeStrategy constructor.
     * @param Summit $summit
     * @param ITransactionService $tx_service
     * @param array $data
     */
    public function __construct(Summit $summit,
                                ITransactionService $tx_service,
                                array $data)
    {
        $this->summit = $summit;
        $this->tx_service = $tx_service;
        $this->data = $data;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return SummitRegistrationPromoCode|null
     * @throws \Exception
     */
    public function getPromoCode(PresentationSpeaker $speaker): ?SummitRegistrationPromoCode {

        return $this->tx_service->transaction(function () use ($speaker) {
            $promo_code = $this->summit->getPromoCodeByCode($this->data["promo_code"]);
            if (is_null($promo_code)) {
                throw new EntityNotFoundException('promo code not found!');
            }
            if ($promo_code::ClassName != SpeakersSummitRegistrationPromoCode::ClassName &&
                $promo_code::ClassName != SpeakersRegistrationDiscountCode::ClassName) {
                throw new ValidationException('invalid promo code');
            }
            $promo_code->assignSpeaker($speaker);
            return $promo_code;
        });
    }
}