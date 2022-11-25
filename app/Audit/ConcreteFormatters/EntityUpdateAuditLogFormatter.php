<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Models\Utils\BaseEntity;
use DateTime;
use ReflectionClass;

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

/**
 * Class EntityUpdateAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityUpdateAuditLogFormatter implements IAuditLogFormatter
{
    protected function getIgnoredFields() {
        return [
            'last_created',
            'last_updated',
            'last_edited'
        ];
    }

    /**
     * @inheritDoc
     */
    public function format($subject, $change_set): ?string {
        $res = [];
        $class_name = (new ReflectionClass($subject))->getShortName();
        $ignored_fields = $this->getIgnoredFields();

        foreach (array_keys($change_set) as $prop_name) {
            if (in_array($prop_name, $ignored_fields)) continue;

            $change_values = $change_set[$prop_name];

            $old_value = $change_values[0];
            $new_value = $change_values[1];

            if ($old_value instanceof BaseEntity || $new_value instanceof BaseEntity) {
                $res[] = "Property \"{$prop_name}\" of entity \"{$class_name}\" has changed";
                continue;
            }

            if ($old_value instanceof DateTime || $new_value instanceof DateTime) {
                $old_value = $old_value != null ? $old_value->format('Y-m-d H:i:s') : "";
                $new_value = $new_value != null ? $new_value->format('Y-m-d H:i:s') : "";
            } else {
                $old_value = print_r($old_value, true);
                $new_value = print_r($new_value, true);
            }

            $res[] = "Property \"{$prop_name}\" of entity \"{$class_name}\" has changed from \"{$old_value}\" to \"{$new_value}\"";
        }

        if (count($res) == 0) return null;

        return join("|",$res);
    }
}