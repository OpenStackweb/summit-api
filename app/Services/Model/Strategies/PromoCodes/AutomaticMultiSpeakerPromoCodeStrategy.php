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
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use services\model\ISummitPromoCodeService;

/**
 * Class AutomaticMultiSpeakerPromoCodeStrategy
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class AutomaticMultiSpeakerPromoCodeStrategy implements IPromoCodeStrategy {
  /**
   * @var IPromoCodeGenerator
   */
  protected $code_generator;

  /**
   * @var Summit
   */
  private $summit;

  /**
   * @var ISummitPromoCodeService
   */
  private $service;

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
   * @param ISummitPromoCodeService $service
   * @param ISummitRegistrationPromoCodeRepository $repository
   * @param IPromoCodeGenerator $code_generator
   * @param array $data
   */
  public function __construct(
    Summit $summit,
    ISummitPromoCodeService $service,
    ISummitRegistrationPromoCodeRepository $repository,
    IPromoCodeGenerator $code_generator,
    array $data,
  ) {
    $this->summit = $summit;
    $this->service = $service;
    $this->repository = $repository;
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
    Log::debug(
      sprintf("AutomaticMultiSpeakerPromoCodeStrategy::getPromoCode speaker %s", $speaker->getId()),
    );
    $code = null;
    do {
      $code = $this->code_generator->generate($this->summit);
    } while ($this->repository->getByCode($code) != null);

    $promo_code_spec = $this->data["promo_code_spec"];
    $promo_code_spec["code"] = $code;
    $promo_code_spec["speaker_ids"] = [$speaker->getId()];

    $promo_code = $this->service->addPromoCode($this->summit, $promo_code_spec);

    if (is_null($promo_code)) {
      throw new ValidationException(
        "Cannot build a valid promo code with the given specification.",
      );
    }

    return $promo_code;
  }
}
