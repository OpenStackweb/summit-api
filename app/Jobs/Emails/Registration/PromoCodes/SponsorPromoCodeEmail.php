<?php namespace App\Jobs\Emails\Registration\PromoCodes;
/**
 * Copyright 2024 OpenStack Foundation
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
use Illuminate\Support\Facades\Log;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class SponsorPromoCodeEmail
 * @package App\Jobs\Emails\Registration
 */
class SponsorPromoCodeEmail extends AbstractSummitEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_SPONSOR_PROMO_CODE';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_SPONSOR_PROMO_CODE';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_SPONSOR_PROMO_CODE';

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @param string|null $test_email_recipient
     */
    public function __construct
    (
        SummitRegistrationPromoCode $promo_code,
        ?string $test_email_recipient
    )
    {
        Log::debug("SponsorPromoCodeEmail::__construct");

        if (!$promo_code instanceof SponsorSummitRegistrationPromoCode &&
            !$promo_code instanceof SponsorSummitRegistrationDiscountCode)
            throw new \InvalidArgumentException('Promo code is not a sponsor promo code.');

        $summit = $promo_code->getSummit();
        $payload = [];
        $sponsor = $promo_code->getSponsor();
        $payload[IMailTemplatesConstants::sponsor_tier_name] = implode(',', $sponsor->getSponsorshipTierNames());
        $payload[IMailTemplatesConstants::promo_code] = $promo_code->getCode();
        $payload[IMailTemplatesConstants::company_name] = '';
        $company = $sponsor->getCompany();
        if (!is_null($company))
            $payload[IMailTemplatesConstants::company_name] = $company->getName();

        $recipient = $promo_code->getContactEmail();
        if(empty($recipient))
            throw new \InvalidArgumentException('promo code contact email is empty.');

        $payload[IMailTemplatesConstants::contact_email] = $recipient;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "SponsorPromoCodeEmail::__construct replacing original email %s by %s",
                    $recipient,
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::contact_email] = $test_email_recipient;
            $recipient = $test_email_recipient;
        }
        parent::__construct($summit, $payload, $template_identifier, $recipient);
        Log::debug(sprintf("SponsorPromoCodeEmail::__construct %s", $this->template_identifier));
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = parent::getEmailTemplateSchema();
        $payload[IMailTemplatesConstants::promo_code]['type'] = 'string';
        $payload[IMailTemplatesConstants::company_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::contact_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::sponsor_tier_name]['type'] = 'string';
        return $payload;
    }
}