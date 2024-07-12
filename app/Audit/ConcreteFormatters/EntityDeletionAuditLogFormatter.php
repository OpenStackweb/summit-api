<?php

namespace App\Audit\ConcreteFormatters;

/**
 * Copyright 2022 OpenStack Foundation
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

use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use App\Audit\IAuditLogFormatter;
use models\summit\SummitAttendeeBadgePrint;
use ReflectionClass;

/**
 * Class EntityDeletionAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityDeletionAuditLogFormatter implements IAuditLogFormatter {
  /**
   * @var IChildEntityAuditLogFormatter
   */
  private $child_entity_formatter;

  public function __construct(?IChildEntityAuditLogFormatter $child_entity_formatter) {
    $this->child_entity_formatter = $child_entity_formatter;
  }

  protected function getCreationIgnoredEntities(): array {
    return ["PresentationAction", "PresentationExtraQuestionAnswer"];
  }

  /**
   * @inheritDoc
   */
  public function format($subject, $change_set): ?string {
    $class_name = (new ReflectionClass($subject))->getShortName();
    $ignored_entities = $this->getCreationIgnoredEntities();
    if (in_array($class_name, $ignored_entities)) {
      return null;
    }

    if ($this->child_entity_formatter != null) {
      return $this->child_entity_formatter->format(
        $subject,
        IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION,
      );
    }

    return "{$class_name} deleted";
  }
}
