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
use Illuminate\Support\Facades\Log;
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
        Log::debug
        (
            sprintf
            (
                "AbstractSummitEmailJob::construct summit %s payload %s to_email %s",
                $summit->getId(),
                json_encode($payload),
                $to_email
            )
        );

        // inject summit common data
        $payload[IMailTemplatesConstants::summit_id]   = $summit->getId();
        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();

        $summitBeginDate = $summit->getLocalBeginDate();
        $payload[IMailTemplatesConstants::summit_date] = !is_null($summitBeginDate)? $summitBeginDate->format("F d, Y") : "";
        $payload[IMailTemplatesConstants::summit_dates_label] = $summit->getDatesLabel();
        $payload[IMailTemplatesConstants::summit_schedule_url] = $summit->getScheduleDefaultPageUrl();
        $payload[IMailTemplatesConstants::summit_site_url] = $summit->getLink();
        $payload[IMailTemplatesConstants::registration_link] = $summit->getRegistrationLink();
        $payload[IMailTemplatesConstants::virtual_event_site_link] = $summit->getVirtualSiteUrl();

        $marketing_api = App::make(IMarketingApi::class);
        $marketing_vars = $marketing_api->getConfigValues($summit->getId(), 'EMAIL_TEMPLATE_');
        if(count($marketing_vars) > 0) {
            Log::debug
            (
                sprintf
                (
                    "AbstractSummitEmailJob::construct summit %s injecting marketing_vars %s",
                    $summit->getId(),
                    json_encode($marketing_vars)
                )
            );
            $payload = array_merge($payload, $marketing_vars);
        }
        parent::__construct($payload, $template_identifier, $to_email, $subject, $cc_email, $bcc_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::summit_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_dates_label]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_schedule_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::registration_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::virtual_event_site_link]['type'] = 'string';

        return $payload;
    }
}