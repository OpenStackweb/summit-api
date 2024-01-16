<?php namespace App\Jobs\Emails;
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

use Illuminate\Support\Facades\App;
use models\summit\Summit;
use services\apis\IMarketingAPI;

/**
 * Class AbstractSummitEmailJob
 * @package App\Jobs\Emails
 */
abstract class AbstractSummitEmailJob extends AbstractEmailJob
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @param string|null $template_identifier
     * @param string $to_email
     * @param string|null $subject
     * @param string|null $cc_email
     * @param string|null $bcc_email
     */
    public function __construct
    (
        Summit $summit,
        array $payload,
        ?string $template_identifier,
        string $to_email,
        ?string $subject = null,
        ?string $cc_email = null,
        ?string $bcc_email = null
    )
    {
        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();

        $summitBeginDate = $summit->getLocalBeginDate();
        $payload[IMailTemplatesConstants::summit_date] = !is_null($summitBeginDate)? $summitBeginDate->format("F d, Y") : "";
        $payload[IMailTemplatesConstants::summit_dates_label] = $summit->getDatesLabel();

        $marketing_api = App::make(IMarketingApi::class);
        $marketing_vars = $marketing_api->getConfigValues($summit->getId(), 'EMAIL_TEMPLATE_')['data'];

        foreach ($marketing_vars as $marketing_var) {
            $payload[$marketing_var['key']] = $marketing_var['value'];
        }

        parent::__construct($payload, $template_identifier, $to_email, $subject, $cc_email, $bcc_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_dates_label]['type'] = 'string';

        return $payload;
    }
}