<?php namespace App\Jobs\Emails\Schedule;
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
use models\summit\SummitEvent;
/**
 * Class ShareEventEmail
 * @package App\Jobs\Emails\Schedule
 */
class ShareEventEmail extends AbstractSummitEmailJob {
  /**
   * ShareEventEmail constructor.
   * @param string $from_email
   * @param string $to_email
   * @param string $event_url
   * @param SummitEvent $event
   */
  public function __construct(
    string $from_email,
    string $to_email,
    string $event_url,
    SummitEvent $event,
  ) {
    $summit = $event->getSummit();
    $payload = [];
    $payload[IMailTemplatesConstants::from_email] = $from_email;
    $payload[IMailTemplatesConstants::to_email] = $to_email;
    $payload[IMailTemplatesConstants::event_title] = $event->getTitle();
    $payload[IMailTemplatesConstants::event_description] = $event->getAbstract();
    $payload[IMailTemplatesConstants::event_url] = $event_url;

    $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

    parent::__construct(
      $summit,
      $payload,
      $template_identifier,
      $payload[IMailTemplatesConstants::to_email],
    );
  }

  /**
   * @return array
   */
  public static function getEmailTemplateSchema(): array {
    $payload = parent::getEmailTemplateSchema();

    $payload[IMailTemplatesConstants::from_email]["type"] = "string";
    $payload[IMailTemplatesConstants::to_email]["type"] = "string";
    $payload[IMailTemplatesConstants::event_title]["type"] = "string";
    $payload[IMailTemplatesConstants::event_description]["type"] = "string";
    $payload[IMailTemplatesConstants::event_url]["type"] = "string";

    return $payload;
  }

  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_SCHEDULE_SHARE_EVENT";
  const EVENT_NAME = "SUMMIT_SCHEDULE_SHARE_EVENT";
  const DEFAULT_TEMPLATE = "SUMMIT_SCHEDULE_SHARE_EVENT";
}
