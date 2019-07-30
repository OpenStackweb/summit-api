<?php namespace App\Jobs\Emails\Registration\ExternalIngestion;
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
use App\Jobs\Emails\AbstractEmailJob;
use models\summit\Summit;

/**
 * Class SuccessfulIIngestionEmail
 * @package App\Jobs\Emails\Registration\ExternalIngestion
 */
class SuccessfulIIngestionEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_SUCCESSFUL_EXTERNAL_INGESTION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_SUCCESSFUL_EXTERNAL_INGESTION';
    const DEFAULT_TEMPLATE = 'REGISTRATION_EXTERNAL_INGESTION_SUCCESSFUL';

    /**
     * SuccessfulIIngestionEmail constructor.
     * @param string $email_to
     * @param Summit $summit
     */
    public function __construct(string $email_to, Summit $summit)
    {
        $payload = [];
        $payload['email_to']    = $email_to;
        $payload['summit_id']   = $summit->getId();
        $payload['summit_name'] = $summit->getName();
        $payload['feed_type']   = $summit->getExternalRegistrationFeedType();
        $payload['external_id'] = $summit->getExternalSummitId();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $email_to);
    }

}