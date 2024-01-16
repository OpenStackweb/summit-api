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

use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use models\summit\Summit;

/**
 * Class UnsuccessfulIIngestionEmail
 * @package App\Jobs\Emails\Registration\ExternalIngestion
 */
class UnsuccessfulIIngestionEmail extends AbstractSummitEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_UNSUCCESSFUL_EXTERNAL_INGESTION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_UNSUCCESSFUL_EXTERNAL_INGESTION';
    const DEFAULT_TEMPLATE = 'REGISTRATION_EXTERNAL_INGESTION_UNSUCCESSFUL';

    /**
     * UnsuccessfulIIngestionEmail constructor.
     * @param string $error_message
     * @param string $email_to
     * @param Summit $summit
     */
    public function __construct(string $error_message, string $email_to, Summit $summit)
    {
        $payload = [];
        $payload[IMailTemplatesConstants::error_message] = $error_message;
        $payload[IMailTemplatesConstants::email_to] = $email_to;
        $payload[IMailTemplatesConstants::summit_id] = $summit->getId();
        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::feed_type] = $summit->getExternalRegistrationFeedType();
        $payload[IMailTemplatesConstants::external_id] = $summit->getExternalSummitId();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $email_to);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::error_message]['type'] = 'string';
        $payload[IMailTemplatesConstants::email_to]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::feed_type]['type'] = 'string';
        $payload[IMailTemplatesConstants::external_id]['type'] = 'string';

        return $payload;
    }
}