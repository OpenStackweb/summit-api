<?php namespace App\ModelSerializers\Software;
/*
 * Copyright 2022 OpenStack Foundation
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

use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class OpenStackReleaseComponentSerializer
 * @package App\ModelSerializers\Software
 */
final class OpenStackReleaseComponentSerializer extends AbstractSerializer {
  /**
   * @var array
   */
  protected static $array_mappings = [
    "Id" => "id:json_int",
    "Adoption" => "adoption:json_int",
    "MaturityPoints" => "maturity_points:json_int",
    "HasInstallationGuide" => "has_installation_guide:json_boolean",
    "ComponentId" => "component_id:json_int",
    "ReleaseId" => "release_id:json_int",
  ];

  protected static $expand_mappings = [
    "component" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "component_id",
      "getter" => "getComponent",
      "has" => "hasComponent",
    ],
    "release" => [
      "type" => One2ManyExpandSerializer::class,
      "original_attribute" => "release_id",
      "getter" => "getRelease",
      "has" => "hasRelease",
    ],
  ];
}
