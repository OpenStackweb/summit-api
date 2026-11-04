<?php namespace ModelSerializers;
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
use models\summit\SummitAttendeeCheckInLog;

/**
 * Class SummitAttendeeCheckInLogCSVSerializer
 * @package ModelSerializers
 */
final class SummitAttendeeCheckInLogCSVSerializer extends SummitAttendeeCheckInLogSerializer
{
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $log = $this->object;
        if (!$log instanceof SummitAttendeeCheckInLog) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $actor = $log->getActor();
        $values['actor_name'] = is_null($actor) ? '' : $actor->getFullName();
        $values['actor_email'] = is_null($actor) ? '' : $actor->getEmail();
        return $values;
    }
}
