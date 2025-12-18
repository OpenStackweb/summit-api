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
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use Illuminate\Support\Facades\Log;

class PresentationTrackChairScoreTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationTrackChairScoreType) {
            return null;
        }

        try {
            $label = $subject->getLabel() ?? 'Unknown';
            $score = $subject->getScore() ?? 'unknown';
            $rating_type = $subject->getRatingType();
            $rating_type_name = $rating_type ? $rating_type->getName() : 'Unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Score Type '%s' (value: %s) added to Rating Type '%s' by user %s",
                        $label,
                        $score,
                        $rating_type_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Score Type '%s' updated: %s by user %s",
                        $label,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Score Type '%s' removed from Rating Type '%s' by user %s",
                        $label,
                        $rating_type_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationTrackChairScoreTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
