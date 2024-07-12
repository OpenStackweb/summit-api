<?php namespace App\Models\Utils\Traits;
/*
 * Copyright 2024 OpenStack Foundation
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

use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
/**
 * Trait CachedEntity
 * @package App\Models\Utils\Traits
 */
trait CachedEntity {
  /**
   * @return string
   */
  protected function _getClassName(): string {
    try {
      return (new ReflectionClass($this))->getShortName();
    } catch (\Exception $ex) {
    }
    return "";
  }

  /**
   * @ORM\preRemove:
   */
  public function deleted($args) {
    Cache::tags(sprintf("%s_%s", $this->_getClassName(), $this->id))->flush();
  }

  public function updated($args) {
    Log::debug(sprintf("CachedEntity::updated id %s", $this->id));
    Cache::tags(sprintf("%s_%s", $this->_getClassName(), $this->id))->flush();
  }
}
