<?php namespace App\Audit\ConcreteFormatters;
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
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Utils\BaseEntity;
use DateTime;
use models\main\File;
use models\main\Member;
use models\summit\PresentationCategory;
use models\summit\PresentationSpeaker;
use models\summit\SummitAbstractLocation;
use models\summit\SummitEventType;
use models\utils\IEntity;
use ReflectionClass;

/**
 * Class EntityUpdateAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityUpdateAuditLogFormatter implements IAuditLogFormatter {
  /**
   * @var IChildEntityAuditLogFormatter
   */
  private $child_entity_formatter;

  public function __construct(?IChildEntityAuditLogFormatter $child_entity_formatter) {
    $this->child_entity_formatter = $child_entity_formatter;
  }

  protected function getIgnoredFields() {
    return ["last_created", "last_updated", "last_edited", "created_by", "updated_by"];
  }

  /**
   * @param string $parent_class
   * @param string $prop_name
   * @param IEntity|null $old_value
   * @param IEntity|null $new_value
   * @param $class
   * @param callable $formatter
   * @return string|null
   */
  private static function formatEntity(
    string $parent_class,
    string $prop_name,
    ?IEntity $old_value,
    ?IEntity $new_value,
    callable $formatter,
  ): ?string {
    $msg = "Property \"{$prop_name}\" of entity \"{$parent_class}\" has changed from ";

    if (is_null($old_value)) {
      $msg .= " TBD ";
    } else {
      $msg .= " \"{$formatter($old_value)})\" ";
    }
    $msg .= " to ";
    if (is_null($new_value)) {
      $msg .= " TBD ";
    } else {
      $msg .= " \"{$formatter($new_value)})\" ";
    }
    return $msg;
  }

  /**
   * @inheritDoc
   */
  public function format($subject, $change_set): ?string {
    $res = [];
    $class_name = (new ReflectionClass($subject))->getShortName();
    $ignored_fields = $this->getIgnoredFields();

    foreach (array_keys($change_set) as $prop_name) {
      if (in_array($prop_name, $ignored_fields)) {
        continue;
      }

      $change_values = $change_set[$prop_name];

      $old_value = $change_values[0];
      $new_value = $change_values[1];

      if ($this->child_entity_formatter != null) {
        $res[] = $this->child_entity_formatter->format(
          $subject,
          IChildEntityAuditLogFormatter::CHILD_ENTITY_UPDATE,
          "Property \"{$prop_name}\" has changed from \"{$old_value}\" to \"{$new_value}\"",
        );
        continue;
      }

      if ($old_value instanceof BaseEntity || $new_value instanceof BaseEntity) {
        $res[] = "Property \"{$prop_name}\" of entity \"{$class_name}\" has changed";
        if (
          $old_value instanceof SummitAbstractLocation ||
          $new_value instanceof SummitAbstractLocation
        ) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getName()} ({$value->getId()})\" ";
          });
        } elseif (
          $old_value instanceof PresentationCategory ||
          $new_value instanceof PresentationCategory
        ) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getTitle()} ({$value->getId()})\" ";
          });
        } elseif ($old_value instanceof SelectionPlan || $new_value instanceof SelectionPlan) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getName()} ({$value->getId()})\" ";
          });
        } elseif ($old_value instanceof SummitEventType || $new_value instanceof SummitEventType) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getType()} ({$value->getId()})\" ";
          });
        } elseif ($old_value instanceof Member || $new_value instanceof Member) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getFullName()} ({$value->getEmail()})\" ";
          });
        } elseif (
          $old_value instanceof PresentationSpeaker ||
          $new_value instanceof PresentationSpeaker
        ) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getFullName()} ({$value->getEmail()})\" ";
          });
        } elseif ($old_value instanceof File || $new_value instanceof File) {
          $res[] = self::formatEntity($class_name, $prop_name, $old_value, $new_value, function (
            $value,
          ) {
            return " \"{$value->getFilename()} ({$value->getId()})\" ";
          });
        }
        continue;
      }

      if ($old_value instanceof DateTime || $new_value instanceof DateTime) {
        $old_value = $old_value != null ? $old_value->format("Y-m-d H:i:s") : "";
        $new_value = $new_value != null ? $new_value->format("Y-m-d H:i:s") : "";
      } elseif (is_bool($old_value) || is_bool($new_value)) {
        $old_value = $old_value ? "true" : "false";
        $new_value = $new_value ? "true" : "false";
      } else {
        $old_value = print_r($old_value, true);
        $new_value = print_r($new_value, true);
      }

      if ($old_value != $new_value) {
        $res[] = "Property \"{$prop_name}\" of entity \"{$class_name}\" has changed from \"{$old_value}\" to \"{$new_value}\"";
      }
    }

    if (count($res) == 0) {
      return null;
    }

    return join("|", $res);
  }
}
