<?php namespace repositories\main;
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
use App\Models\Foundation\Main\Repositories\ISponsoredProjectRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\main\SponsoredProject;
/**
 * Class DoctrineSponsoredProjectRepository
 * @package repositories\main
 */
final class DoctrineSponsoredProjectRepository extends SilverStripeDoctrineRepository implements
  ISponsoredProjectRepository {
  /**
   * @return array
   */
  protected function getFilterMappings() {
    return [
      "name" => "e.name:json_string",
      "slug" => "e.slug:json_string",
      "is_active" => "e.is_active:json_int",
      "parent_project_id" => "e.parent_project:json_int",
    ];
  }

  /**
   * @return array
   */
  protected function getOrderMappings() {
    return [
      "id" => "e.id",
      "name" => "e.name",
    ];
  }

  /**
   * @inheritDoc
   */
  protected function getBaseEntity() {
    return SponsoredProject::class;
  }

  public function getByName(string $name): ?SponsoredProject {
    return $this->findOneBy(["name" => trim($name)]);
  }
}
