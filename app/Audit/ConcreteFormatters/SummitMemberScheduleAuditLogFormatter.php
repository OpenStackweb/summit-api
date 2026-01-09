<?php namespace App\Audit\ConcreteFormatters;
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
use models\main\SummitMemberSchedule;

class SummitMemberScheduleAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function __construct()
    {
        parent::__construct('summit_member_schedule');
    }

    /**
     * @param $subject
     * @param array $change_set
     * @return string|null
     */
    public function format($subject, array $change_set): ?string
    {
       if(!$subject instanceof SummitMemberSchedule) return null;
       $summit_event = $subject->getEvent();
       $event_title = $summit_event ? ($summit_event->getTitle() ?? 'Unknown Event') : 'Unknown Event';
       $event_id = $summit_event ? ($summit_event->getId() ?? 'unknown') : 'unknown';
       
       switch($this->event_type) {
           case IAuditStrategy::EVENT_ENTITY_CREATION:
               return sprintf(
                   "Activity '%s' (%s) was inserted onto user custom schedule by user %s",
                   $event_title,
                   $event_id,
                   $this->getUserInfo()
               );

           case IAuditStrategy::EVENT_ENTITY_UPDATE:
               $change_details = $this->buildChangeDetails($change_set);
               return sprintf(
                   "Activity '%s' (%s) in user custom schedule updated: %s by user %s",
                   $event_title,
                   $event_id,
                   $change_details,
                   $this->getUserInfo()
               );

           case IAuditStrategy::EVENT_ENTITY_DELETION:
               return sprintf(
                   "Activity '%s' (%s) was removed from user custom schedule by user %s",
                   $event_title,
                   $event_id,
                   $this->getUserInfo()
               );
       }
       return null;
    }
}
