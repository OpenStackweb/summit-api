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
use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\main\SummitMemberSchedule;

class SummitMemberScheduleAuditLogFormatter implements IAuditLogFormatter
{

    private string $event_type;
    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
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
       switch($this->event_type) {
           case IAuditStrategy::EVENT_ENTITY_DELETION:{
               return sprintf("Activity %s (%s) was removed from user custom schedule.", $summit_event->getTitle(), $summit_event->getId());
           }
           case IAuditStrategy::EVENT_ENTITY_CREATION:{
               return sprintf("Activity %s (%s) was inserted onto user custom schedule.", $summit_event->getTitle(), $summit_event->getId());
           }
       }
       return null;
    }
}
