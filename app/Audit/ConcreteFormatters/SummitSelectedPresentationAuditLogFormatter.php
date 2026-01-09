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
use models\summit\SummitSelectedPresentation;
use Illuminate\Support\Facades\Log;

class SummitSelectedPresentationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSelectedPresentation) {
            return null;
        }

        try {
            $presentation = $subject->getPresentation();
            $presentation_name = $presentation ? ($presentation->getTitle() ?? 'Unknown Presentation') : 'Unknown Presentation';
            $collection = $subject->getCollection();
            $member = $subject->getMember();
            $member_name = $member ? ($member->getFullName() ?? 'Unknown Member') : 'Unknown Member';
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Selected Presentation (%d) '%s' Collection '%s' by '%s' created by user %s",
                        $id,
                        $presentation_name,
                        $collection,
                        $member_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Selected Presentation (%d) '%s' updated: %s by user %s",
                        $id,
                        $presentation_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Selected Presentation (%d) '%s' was deleted by user %s",
                        $id,
                        $presentation_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSelectedPresentationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
