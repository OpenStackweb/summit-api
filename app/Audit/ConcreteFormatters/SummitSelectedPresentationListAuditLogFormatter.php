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
use models\summit\SummitSelectedPresentationList;
use Illuminate\Support\Facades\Log;

class SummitSelectedPresentationListAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSelectedPresentationList) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown List';
            $list_type = $subject->getListType() ?? 'Unknown Type';
            $category = $subject->getCategory();
            $category_name = $category ? ($category->getTitle() ?? 'Unknown Category') : 'Unknown Category';
            $owner = $subject->getOwner();
            $owner_name = $owner ? ($owner->getFullName() ?? 'Unknown Owner') : 'Unknown Owner';
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Selected Presentation List (%d) '%s' Type '%s' Category '%s' Owner '%s' created by user %s",
                        $id,
                        $name,
                        $list_type,
                        $category_name,
                        $owner_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Selected Presentation List (%d) '%s' updated: %s by user %s",
                        $id,
                        $name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Selected Presentation List (%d) '%s' was deleted by user %s",
                        $id,
                        $name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSelectedPresentationListAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
