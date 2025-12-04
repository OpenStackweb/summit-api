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
use models\summit\SummitTrackChair;
use Illuminate\Support\Facades\Log;

class SummitTrackChairAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitTrackChair) {
            return null;
        }

        try {
            $member = $subject->getMember();
            $member_name = $member ? sprintf("%s %s", $member->getFirstName(), $member->getLastName()) : 'Unknown';
            $member_id = $member ? $member->getId() : 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $categories = [];
                    foreach ($subject->getCategories() as $category) {
                        $categories[] = $category->getTitle();
                    }
                    $tracks_list = !empty($categories) ? implode(', ', $categories) : 'No tracks assigned';
                    return sprintf(
                        "Track Chair '%s' (%d) assigned with tracks: %s by user %s",
                        $member_name,
                        $member_id,
                        $tracks_list,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    if (isset($change_set['categories'])) {
                        $old_cats = $change_set['categories'][0] ?? [];
                        $new_cats = $change_set['categories'][1] ?? [];
                        
                        $old_names = is_array($old_cats) 
                            ? array_map(fn($c) => $c->getTitle() ?? 'Unknown', $old_cats)
                            : [];
                        $new_names = is_array($new_cats)
                            ? array_map(fn($c) => $c->getTitle() ?? 'Unknown', $new_cats)
                            : [];
                        
                        $old_str = !empty($old_names) ? implode(', ', $old_names) : 'None';
                        $new_str = !empty($new_names) ? implode(', ', $new_names) : 'None';
                        
                        return sprintf(
                            "Track Chair '%s' tracks changed: [%s] → [%s] by user %s",
                            $member_name,
                            $old_str,
                            $new_str,
                            $this->getUserInfo()
                        );
                    }
                    return sprintf("Track Chair '%s' updated by user %s", $member_name, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Track Chair '%s' (%d) removed from summit by user %s",
                        $member_name,
                        $member_id,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitTrackChairAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
