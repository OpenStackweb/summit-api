<?php

namespace App\Audit;

/**
 * Copyright 2025 OpenStack Foundation
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

abstract class AbstractAuditLogFormatter implements IAuditLogFormatter
{
    protected AuditContext $ctx;

    final public function setContext(AuditContext $ctx): void
    {
        $this->ctx = $ctx;
    }

    protected function getUserInfo(): string
    {
        if (!$this->ctx) {
            return 'Unknown (unknown)';
        }

        $user_name = 'Unknown';
        if ($this->ctx->userFirstName || $this->ctx->userLastName) {
            $user_name = trim(sprintf("%s %s", $this->ctx->userFirstName ?? '', $this->ctx->userLastName ?? '')) ?: 'Unknown';
        } elseif ($this->ctx->userEmail) {
            $user_name = $this->ctx->userEmail;
        }
        
        $user_id = $this->ctx->userId ?? 'unknown';
        return sprintf("%s (%s)", $user_name, $user_id);
    }


    protected function getIgnoredFields(): array
    {
        return [
            'last_created',
            'last_updated',
            'last_edited',
            'created_by',
            'updated_by'
        ];
    }

    protected function formatChangeValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            $count = $value->count();
            return sprintf('Collection[%d items]', $count);
        }
        if (is_object($value)) {
            $className = get_class($value);
            return sprintf('%s', $className);
        }
        if (is_array($value)) {
            return sprintf('Array[%d items]', count($value));
        }
        return (string) $value;
    }


    protected function buildChangeDetails(array $change_set): string
    {
        $changed_fields = [];
        $ignored_fields = $this->getIgnoredFields();

        foreach ($change_set as $prop_name => $change_values) {
            if (in_array($prop_name, $ignored_fields)) {
                continue;
            }

            $old_value = $change_values[0] ?? null;
            $new_value = $change_values[1] ?? null;

            $formatted_change = $this->formatFieldChange($prop_name, $old_value, $new_value);
            if ($formatted_change !== null) {
                $changed_fields[] = $formatted_change;
            }
        }

        if (empty($changed_fields)) {
            return 'properties without changes registered';
        }

        $fields_summary = count($changed_fields) . ' field(s) modified: ';
        return $fields_summary . implode(' | ', $changed_fields);
    }

    protected function formatFieldChange(string $prop_name, $old_value, $new_value): ?string
    {
        $old_display = $this->formatChangeValue($old_value);
        $new_display = $this->formatChangeValue($new_value);

        return sprintf("Property \"%s\" has changed from \"%s\" to \"%s\"", $prop_name, $old_display, $new_display);
    }

    abstract public function format($subject, array $change_set): ?string;
}
