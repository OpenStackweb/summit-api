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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\PresentationMediaUpload;
use Illuminate\Support\Facades\Log;

class PresentationMediaUploadAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationMediaUpload) {
            return null;
        }

        try {
            $title = $subject->getName() ?? 'Unknown Media';
            $id = $subject->getId() ?? 'unknown';
            $class_name = $subject->getClassName();
            
            $presentation = $subject->getPresentation();
            $presentation_title = $presentation ? ($presentation->getTitle() ?? 'Unknown Presentation') : 'Unknown Presentation';
            

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Presentation Media Upload '%s' (%d) of type %s created for presentation '%s' by user %s",
                        $title,
                        $id,
                        $class_name,
                        $presentation_title,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Presentation Media Upload '%s' (%d) for presentation '%s' updated: %s by user %s",
                        $title,
                        $id,
                        $presentation_title,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Presentation Media Upload '%s' (%d) of type %s for presentation '%s' was deleted by user %s",
                        $title,
                        $id,
                        $class_name,
                        $presentation_title,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationMediaUploadAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
