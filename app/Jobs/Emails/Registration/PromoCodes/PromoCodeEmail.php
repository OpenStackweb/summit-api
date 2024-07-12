<?php namespace App\Jobs\Emails\Registration\PromoCodes;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use models\summit\IOwnablePromoCode;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class PromoCodeEmail
 * @package App\Jobs\Emails\Registration
 */
abstract class PromoCodeEmail extends AbstractSummitEmailJob {
  /**
   * PromoCodeEmail constructor.
   * @param SummitRegistrationPromoCode $promo_code
   */
  public function __construct(SummitRegistrationPromoCode $promo_code) {
    if (!$promo_code instanceof IOwnablePromoCode) {
      throw new \InvalidArgumentException("promo code is not ownerable.");
    }

    $summit = $promo_code->getSummit();
    $payload = [];

    $payload[IMailTemplatesConstants::promo_code] = $promo_code->getCode();

    $payload[IMailTemplatesConstants::owner_email] = $promo_code->getOwnerEmail();
    $payload[IMailTemplatesConstants::owner_fullname] = $promo_code->getOwnerFullname();
    if (empty($payload[IMailTemplatesConstants::owner_fullname])) {
      $payload[IMailTemplatesConstants::owner_fullname] =
        $payload[IMailTemplatesConstants::owner_email];
    }

    $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
    parent::__construct(
      $summit,
      $payload,
      $template_identifier,
      $payload[IMailTemplatesConstants::owner_email],
    );
  }

  /**
   * @return array
   */
  public static function getEmailTemplateSchema(): array {
    $payload = parent::getEmailTemplateSchema();

    $payload[IMailTemplatesConstants::owner_email]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_fullname]["type"] = "string";
    $payload[IMailTemplatesConstants::promo_code]["type"] = "string";

    return $payload;
  }
}
