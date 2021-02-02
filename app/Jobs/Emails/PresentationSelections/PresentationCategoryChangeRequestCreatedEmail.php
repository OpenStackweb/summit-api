<?php namespace App\Jobs\Emails\PresentationSelections;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\SummitCategoryChange;
/**
 * Class PresentationCategoryChangeRequestCreatedEmail
 * @package App\Jobs\Emails\PresentationSelections
 */
class PresentationCategoryChangeRequestCreatedEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_CREATED';
    const EVENT_NAME = 'SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_CREATED';
    const DEFAULT_TEMPLATE = 'SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_CREATED';

    public function __construct(SummitCategoryChange $request)
    {

        $to_emails = [];
        $presentation = $request->getPresentation();
        $requester = $request->getRequester();
        $old_category = $request->getOldCategory();
        $new_category = $request->getNewCategory();

        foreach($new_category->getTrackChairs() as $chair){
            $to_emails[] = $chair->getMember()->getEmail();
        }

        $summit = $presentation->getSummit();

        $payload = [];
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_date'] = $summit->getMonthYear();
        $payload['requester_fullname'] = $requester->getFullName();
        $payload['requester_email'] = $requester->getEmail();
        $payload['old_category'] = $old_category->getTitle();
        $payload['new_category'] = $new_category->getTitle();
        $payload['status'] = $request->getNiceStatus();
        $payload['presentation_title'] = $presentation->getTitle();
        $payload['presentation_id'] = $presentation->getId();
        $payload['review_link'] = ''; //TBD
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, implode(",", $to_emails));
    }
}