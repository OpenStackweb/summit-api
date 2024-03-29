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
use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use models\summit\SummitCategoryChange;
use Illuminate\Support\Facades\Config;
/**
 * Class PresentationCategoryChangeRequestCreatedEmail
 * @package App\Jobs\Emails\PresentationSelections
 */
class PresentationCategoryChangeRequestCreatedEmail extends AbstractSummitEmailJob
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
        $payload[IMailTemplatesConstants::requester_fullname] = $requester->getFullName();
        $payload[IMailTemplatesConstants::requester_email] = $requester->getEmail();
        $payload[IMailTemplatesConstants::old_category] = $old_category->getTitle();
        $payload[IMailTemplatesConstants::new_category] = $new_category->getTitle();
        $payload[IMailTemplatesConstants::status] = $request->getNiceStatus();
        $payload[IMailTemplatesConstants::presentation_title] = $presentation->getTitle();
        $payload[IMailTemplatesConstants::presentation_id] = $presentation->getId();
        $payload[IMailTemplatesConstants::review_link] = sprintf(Config::get("track_chairs.review_link"), $summit->getRawSlug(), $presentation->getSelectionPlanId());
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, implode(",", $to_emails));
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::requester_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::old_category]['type'] = 'string';
        $payload[IMailTemplatesConstants::new_category]['type'] = 'string';
        $payload[IMailTemplatesConstants::status]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_title]['type'] = 'string';
        $payload[IMailTemplatesConstants::presentation_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::review_link]['type'] = 'string';

        return $payload;
    }
}