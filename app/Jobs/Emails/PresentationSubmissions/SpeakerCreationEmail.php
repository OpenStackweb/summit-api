<?php namespace App\Jobs\Emails\PresentationSubmissions;
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
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use models\summit\PresentationSpeaker;
/**
 * Class SpeakerCreationEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class SpeakerCreationEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_CREATION';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_CREATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_CREATION';

    /**
     * PresentationSpeakerNotificationEmail constructor.
     * @param PresentationSpeaker $speaker
     */
    public function __construct(PresentationSpeaker $speaker)
    {

        $speaker_management_base_url = Config::get('cfp.base_url');
        $idp_base_url = Config::get('idp.base_url');
        $support_email = Config::get('cfp.support_email');

        if(empty($speaker_management_base_url))
            throw new \InvalidArgumentException('cfp.base_url is null.');

        if(empty($idp_base_url))
            throw new \InvalidArgumentException('idp.base_url is null.');

        if(empty($support_email))
            throw new \InvalidArgumentException('cfp.support_email is null.');

        $payload = [];
        $payload[IMailTemplatesConstants::speaker_full_name] = $speaker->getFullName();
        $payload[IMailTemplatesConstants::speaker_email] = $speaker->getEmail();
        $payload[IMailTemplatesConstants::speaker_management_link] = $speaker_management_base_url;
        $bio_edit_link = sprintf("%s/app/profile", $speaker_management_base_url);
        $registrationRequest = $speaker->getRegistrationRequest();
        /**
         *
         @todo need 2 update CFP to support registration request retrieval
        if(is_null($registrationRequest)){
            $token = $registrationRequest->getToken();
            if(!is_null($token))
                $bio_edit_link = $bio_edit_link.'/'.$token;
        }
         */
        $payload[IMailTemplatesConstants::bio_edit_link] = $bio_edit_link;
        $payload[IMailTemplatesConstants::reset_password_link] = sprintf("%s/auth/password/reset", $idp_base_url);
        $payload[IMailTemplatesConstants::support_email] = $support_email;
        $payload[IMailTemplatesConstants::tenant_name] = Config::get("app.tenant_name");

        parent::__construct($payload, self::DEFAULT_TEMPLATE, $payload[IMailTemplatesConstants::speaker_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::speaker_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_management_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::bio_edit_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::reset_password_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::tenant_name]['type'] = 'string';

        return $payload;
    }
}