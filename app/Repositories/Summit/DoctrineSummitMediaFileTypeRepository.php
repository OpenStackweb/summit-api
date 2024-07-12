<?php namespace App\Repositories\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitMediaFileType;

/**
 * Class DoctrineSummitMediaFileTypeRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitMediaFileTypeRepository extends SilverStripeDoctrineRepository implements
  ISummitMediaFileTypeRepository {
  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "name" => "e.name:json_string",
    ];
  }

  /**
   * @inheritDoc
   */
  protected function getBaseEntity() {
    return SummitMediaFileType::class;
  }

  public function getByName(string $name): ?SummitMediaFileType {
    return $this->findOneBy(["name" => trim($name)]);
  }
}
