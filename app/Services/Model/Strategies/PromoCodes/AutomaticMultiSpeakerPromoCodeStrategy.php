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
use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class AutomaticMultiSpeakerPromoCodeStrategy
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class AutomaticMultiSpeakerPromoCodeStrategy implements IPromoCodeStrategy
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
     * @var Summit
     */
    private $summit;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $data;

    /**
     * PromoCodeStrategy constructor.
     * @param Summit $summit
     * @param ISummitRegistrationPromoCodeRepository $repository
     * @param ITransactionService $tx_service
     * @param IPromoCodeGenerator $code_generator
     * @param array $data
     */
    public function __construct(Summit $summit,
                                ISummitRegistrationPromoCodeRepository $repository,
                                ITransactionService $tx_service,
                                IPromoCodeGenerator $code_generator,
                                array $data)
    {
        $this->summit = $summit;
        $this->repository = $repository;
        $this->tx_service = $tx_service;
        $this->code_generator = $code_generator;
        $this->data = $data;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @return SummitRegistrationPromoCode|null
     * @throws EntityNotFoundException
     * @throws ValidationException|\Exception
     */
    public function getPromoCode(PresentationSpeaker $speaker): ?SummitRegistrationPromoCode {

        return $this->tx_service->transaction(function () use ($speaker) {
            $code = null;
            do {
                $code = $this->code_generator->generate($this->summit);
            } while($this->repository->getByCode($code) != null);

            $promo_code_spec = $this->data["promo_code_spec"];
            $promo_code_spec["code"] = $code;
            $promo_code = SummitPromoCodeFactory::build($this->summit, $promo_code_spec);
            if (is_null($promo_code)) {
                throw new ValidationException('cannot build a valid promo code with the given specification');
            }
            $promo_code->assignSpeaker($speaker);
            return $promo_code;
        });
    }
}