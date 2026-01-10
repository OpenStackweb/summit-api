<?php

namespace App\Audit\ConcreteFormatters;

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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata;
use Illuminate\Support\Facades\Log;

class SummitRegistrationFeedMetadataAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitRegistrationFeedMetadata) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $key = $subject->getKey() ?? 'Unknown Key';
                    $value = $subject->getValue() ?? 'Unknown Value';
                    $summit_id = $subject->getSummitId() ?? 'unknown';
                    return sprintf(
                        "Summit Registration Feed Metadata (%s) key '%s' value '%s' created for summit %s by user %s",
                        $id,
                        $key,
                        $value,
                        $summit_id,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $key = $subject->getKey() ?? 'Unknown Key';
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Summit Registration Feed Metadata (%s) key '%s' updated: %s by user %s",
                        $id,
                        $key,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $key = $subject->getKey() ?? 'Unknown Key';
                    return sprintf(
                        "Summit Registration Feed Metadata (%s) key '%s' was deleted by user %s",
                        $id,
                        $key,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitRegistrationFeedMetadataAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
