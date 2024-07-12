<?php namespace App\ModelSerializers\Summit;
/*
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
use ModelSerializers\SerializerRegistry;

/**
 * Class AdminSummitAttendeeNoteSerializer
 * @package App\ModelSerializers\Summit\Registration
 */
class AdminSummitAttendeeNoteSerializer extends SummitAttendeeNoteSerializer {
  protected static $expand_mappings = [
    "author" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "author_id",
      "getter" => "getAuthor",
      "has" => "hasAuthor",
      "serializer_type" => SerializerRegistry::SerializerType_Private,
    ],
    "owner" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "owner_id",
      "getter" => "getOwner",
      "has" => "hasOwner",
      "serializer_type" => SerializerRegistry::SerializerType_Private,
    ],
    "ticket" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "ticket_id",
      "getter" => "getTicket",
      "has" => "hasTicket",
    ],
  ];
}
