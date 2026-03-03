<?php

namespace App\Audit\ConcreteFormatters;

/**
 * Copyright 2026 OpenStack Foundation
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
use App\Models\Foundation\Summit\Signs\SummitSign;
use Illuminate\Support\Facades\Log;

class SummitSignAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSign) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $template = $subject->getTemplate() ?? 'Unknown Template';
            $location = $subject->getLocation();
            $location_name = $location ? ($location->getName() ?? 'Unknown Location') : 'No Location';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Summit Sign (%s) created for Summit '%s' at Location '%s' with template '%s' by user %s",
                        $id,
                        $summit_name,
                        $location_name,
                        $template,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Summit Sign (%s) for Summit '%s' at Location '%s' updated: %s by user %s",
                        $id,
                        $summit_name,
                        $location_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Summit Sign (%s) for Summit '%s' at Location '%s' was deleted by user %s",
                        $id,
                        $summit_name,
                        $location_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSignAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
