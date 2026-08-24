<?php namespace App\Jobs\Emails\Schedule;
/**
 * Copyright 2026 OpenStack Foundation
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
use Illuminate\Support\Facades\Config;
use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
/**
 * Class PresentationActivitySpeakerChangeEmail
 * @package App\Jobs\Emails\Schedule
 */
class PresentationActivitySpeakerChangeEmail extends AbstractSummitEmailJob
{
    const Role_Speaker = 'Speaker';
    const Role_Moderator = 'Moderator';

    const AllowedRoles = [self::Role_Speaker, self::Role_Moderator];

    const Action_Added = 'Added';
    const Action_Removed = 'Removed';

    const AllowedActions = [self::Action_Added, self::Action_Removed];

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PRESENTATION_ACTIVITY_SPEAKER_CHANGE';
    const EVENT_NAME = 'SUMMIT_PRESENTATION_ACTIVITY_SPEAKER_CHANGE';
    const DEFAULT_TEMPLATE = 'SUMMIT_PRESENTATION_ACTIVITY_SPEAKER_CHANGE';

    /**
     * PresentationActivitySpeakerChangeEmail constructor.
     * @param Presentation $presentation
     * @param PresentationSpeaker $speaker
     * @param string $role
     * @param string $action
     */
    public function __construct(Presentation $presentation, PresentationSpeaker $speaker, string $role, string $action)
    {
        if (!in_array($role, self::AllowedRoles))
            throw new \InvalidArgumentException(sprintf('role %s is not a valid role.', $role));

        if (!in_array($action, self::AllowedActions))
            throw new \InvalidArgumentException(sprintf('action %s is not a valid action.', $action));

        $summit = $presentation->getSummit();

        $payload = [];
        $payload[IMailTemplatesConstants::speaker_full_name] = $speaker->getFullName(" ");
        $payload[IMailTemplatesConstants::speaker_email] = $speaker->getEmail();
        $payload[IMailTemplatesConstants::presentation_title] = $presentation->getTitle();
        $payload[IMailTemplatesConstants::presentation_id] = $presentation->getId();
        $payload[IMailTemplatesConstants::presentation_edit_link] = $presentation->getEditLink();
        $payload[IMailTemplatesConstants::activity_change_role] = $role;
        $payload[IMailTemplatesConstants::activity_change_action] = $action;

        $to_email = Config::get('cfp.speaker_change_notification_email');
        if (empty($to_email))
            throw new ValidationException('cfp.speaker_change_notification_email is not configured.');

        parent::__construct($summit, $payload, self::DEFAULT_TEMPLATE, $to_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::speaker_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::speaker_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_title]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::presentation_edit_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::activity_change_role]['type'] = 'string';
        $payload[IMailTemplatesConstants::activity_change_action]['type'] = 'string';

        return $payload;
    }
}
