<?php namespace App\ModelSerializers\Traits;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Trait RequestCache
 * @package App\ModelSerializers\Traits
 */
trait RequestCache {
  /**
   * @param string $scope
   * @param string $key
   * @param Closure $callback
   * @param array $params
   * @return mixed
   */
  function cache(string $scope, string $key, Closure $callback, array $params = []) {
    Log::debug(sprintf("RequestCache::cache scope %s key %s.", $scope, $key));
    $bypass = $params["bypass_cache"] ?? false;

    if ($bypass) {
      Log::debug(sprintf("RequestCache::cache scope %s key %s bypassing cache.", $scope, $key));
      return $callback();
    }

    $res = Cache::tags($scope)->get($key);
    if (!empty($res)) {
      Log::debug(sprintf("RequestCache::cache scope %s key %s cache hit", $scope, $key));
      return json_decode(gzinflate($res), true);
    }
    $res = $callback();
    Log::debug(sprintf("RequestCache::cache scope %s key %s adding to cache.", $scope, $key));
    Cache::tags($scope)->add($key, gzdeflate(json_encode($res), 9));
    return $res;
  }
}
