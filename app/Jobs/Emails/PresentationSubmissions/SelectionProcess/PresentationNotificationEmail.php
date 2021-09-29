<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
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
use models\summit\Presentation;
/**
 * Class PresentationNotificationEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
abstract class PresentationNotificationEmail extends AbstractEmailJob
{
    /**
     * PresentationNotificationEmail constructor.
     * @param Presentation $presentation
     * @param string $to
     */
    function __construct
    (
        Presentation $presentation,
        string $to
    ){
        $payload = [];
        $summit = $presentation->getSummit();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_schedule_url'] = $summit->getScheduleDefaultPageUrl();
        $payload['summit_site_url'] = $summit->getDefaultPageUrl();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $to);
    }
}