<?php

namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

use models\summit\SummitAttendeeBadgePrint;

/**
 * Copyright 2023 OpenStack Foundation
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

/**
 * Class SummitAttendeeBadgePrintAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class SummitAttendeeBadgePrintAuditLogFormatter implements IChildEntityAuditLogFormatter {
  /**
   * @inheritDoc
   */
  public function format(
    $subject,
    string $child_entity_action_type,
    ?string $additional_info = "",
  ): ?string {
    if (
      $child_entity_action_type == IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION &&
      $subject instanceof SummitAttendeeBadgePrint
    ) {
      $print_date = $subject->getPrintDate()->format("Y-m-d H:i:s");
      $view_type_name = "N/A";
      $view_type = $subject->getViewType();
      if (!is_null($view_type)) {
        $view_type_name = $view_type->getName();
      }
      $requestor = $subject->getRequestor();
      return "SummitAttendeeBadgePrint with ID {$subject->getID()}, printed on {$print_date}, view type {$view_type_name} and requestor member {$requestor->getEmail()} ({$requestor->getId()}) was removed";
    }
    return "";
  }
}
