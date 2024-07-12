<?php namespace App\Models\Foundation\Summit\Factories;
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

use models\summit\SponsorMaterial;

/**
 * Class SponsorMaterialFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorMaterialFactory {
  /**
   * @param array $data
   * @return SponsorMaterial
   */
  public static function build(array $data): SponsorMaterial {
    return self::populate(new SponsorMaterial(), $data);
  }

  /**
   * @param SponsorMaterial $material
   * @param array $data
   * @return SponsorMaterial
   */
  public static function populate(SponsorMaterial $material, array $data): SponsorMaterial {
    if (isset($data["type"])) {
      $material->setType(trim($data["type"]));
    }
    if (isset($data["name"])) {
      $material->setName(trim($data["name"]));
    }
    if (isset($data["link"])) {
      $material->setLink(trim($data["link"]));
    }
    return $material;
  }
}
