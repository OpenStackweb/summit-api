<?php
namespace App\Jobs\Emails\Registration\PromoCodes;

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
/**
 * Class SpeakerPromoCodeEMail
 * @package App\Jobs\Emails\Registration
 */
class SpeakerPromoCodeEMail extends PromoCodeEmail
{


    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_SPEAKER_PROMO_CODE';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_SPEAKER_PROMO_CODE';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_SPEAKER_PROMO_CODE';
}