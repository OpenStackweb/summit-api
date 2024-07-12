<?php namespace App\ModelSerializers\Audit;
/**
 * Copyright 2023 OpenStack Foundation
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

use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class SummitAttendeeBadgeAuditLogSerializer
 * @package ModelSerializers
 */
class SummitAttendeeBadgeAuditLogSerializer extends SummitAuditLogSerializer {
  protected static $array_mappings = [
    "SummitAttendeeBadgeID" => "attendee_badge_id:json_int",
  ];

  protected static $expand_mappings = [
    "event" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "attendee_badge_id",
      "getter" => "getAttendeeBadge",
      "has" => "hasSummitAttendeeBadge",
    ],
  ];
}
