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

/**
 * Class PresentationCategoryChangeRequestResolvedEmail
 * @package App\Jobs\Emails\PresentationSelections
 */
class PresentationCategoryChangeRequestResolvedEmail extends AbstractSummitEmailJob {
  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_RESOLVED";
  const EVENT_NAME = "SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_RESOLVED";
  const DEFAULT_TEMPLATE = "SUMMIT_SELECTIONS_PRESENTATION_CATEGORY_CHANGE_REQUEST_RESOLVED";

  public function __construct(SummitCategoryChange $request) {
    $to_emails = [];
    $presentation = $request->getPresentation();
    $aprover = $request->getAprover();
    $requester = $request->getRequester();
    $old_category = $request->getOldCategory();
    $new_category = $request->getNewCategory();

    foreach ($old_category->getTrackChairs() as $chair) {
      $to_emails[] = $chair->getMember()->getEmail();
    }
    foreach ($new_category->getTrackChairs() as $chair) {
      $to_emails[] = $chair->getMember()->getEmail();
    }

    $summit = $presentation->getSummit();
    $payload = [];
    $payload[IMailTemplatesConstants::aprover_fullname] = $aprover->getFullName();
    $payload[IMailTemplatesConstants::aprover_email] = $aprover->getEmail();
    $payload[IMailTemplatesConstants::requester_fullname] = $requester->getFullName();
    $payload[IMailTemplatesConstants::requester_email] = $requester->getEmail();
    $payload[IMailTemplatesConstants::old_category] = $old_category->getTitle();
    $payload[IMailTemplatesConstants::new_category] = $new_category->getTitle();
    $payload[IMailTemplatesConstants::status] = $request->getNiceStatus();
    $payload[IMailTemplatesConstants::presentation_title] = $presentation->getTitle();
    $payload[IMailTemplatesConstants::presentation_id] = $presentation->getId();
    $payload[IMailTemplatesConstants::reason] = $request->getReason();
    $payload[IMailTemplatesConstants::approval_date] = $request
      ->getApprovalDate()
      ->format("d F, Y");
    $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

    parent::__construct($summit, $payload, $template_identifier, implode(",", $to_emails));
  }

  /**
   * @return array
   */
  public static function getEmailTemplateSchema(): array {
    $payload = parent::getEmailTemplateSchema();

    $payload[IMailTemplatesConstants::aprover_fullname]["type"] = "string";
    $payload[IMailTemplatesConstants::aprover_email]["type"] = "string";
    $payload[IMailTemplatesConstants::requester_fullname]["type"] = "string";
    $payload[IMailTemplatesConstants::requester_email]["type"] = "string";
    $payload[IMailTemplatesConstants::old_category]["type"] = "string";
    $payload[IMailTemplatesConstants::new_category]["type"] = "string";
    $payload[IMailTemplatesConstants::status]["type"] = "string";
    $payload[IMailTemplatesConstants::presentation_title]["type"] = "string";
    $payload[IMailTemplatesConstants::presentation_id]["type"] = "int";
    $payload[IMailTemplatesConstants::reason]["type"] = "string";
    $payload[IMailTemplatesConstants::approval_date]["type"] = "string";

    return $payload;
  }
}
