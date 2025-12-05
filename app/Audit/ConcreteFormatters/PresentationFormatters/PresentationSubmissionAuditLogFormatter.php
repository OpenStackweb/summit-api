<?php

namespace App\Audit\ConcreteFormatters\PresentationFormatters;

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

class PresentationSubmissionAuditLogFormatter extends BasePresentationAuditLogFormatter
{
    protected function formatCreation(array $data): string
    {
        return sprintf(
            "Presentation '%s' (%s) submitted by '%s' to track '%s' (Plan: %s) by user %s",
            $data['title'],
            $data['id'],
            $data['creator_name'],
            $data['category_name'],
            $data['plan_name'],
            $this->getUserInfo()
        );
    }

    protected function formatUpdate(array $data, array $extracted): string
    {
        if ($extracted['old_status'] && $extracted['new_status']) {
            return sprintf(
                "Presentation '%s' (%s) status changed: %s → %s (%s changed) by user %s",
                $data['title'],
                $data['id'],
                strtoupper($extracted['old_status']),
                strtoupper($extracted['new_status']),
                $extracted['fields'],
                $this->getUserInfo()
            );
        }

        return sprintf(
            "Presentation '%s' (%s) updated (%s changed) by user %s",
            $data['title'],
            $data['id'],
            $extracted['fields'],
            $this->getUserInfo()
        );
    }

    protected function formatDeletion(array $data): string
    {
        return sprintf(
            "Presentation '%s' (%s) submitted by '%s' to track '%s' was deleted by user %s",
            $data['title'],
            $data['id'],
            $data['creator_name'],
            $data['category_name'],
            $this->getUserInfo()
        );
    }
}
