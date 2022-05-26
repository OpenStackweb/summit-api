<?php namespace App\ModelSerializers\Traits;
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
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Uuid;

/**
 * Trait RequestScopedCache
 */
trait RequestScopedCache
{

    /**
     * @return string
     */
    static function getScopeId():string{
        $request = request();
        $requestId = Request::header('X-Request-ID', null);
        if(is_null($requestId)){

            $ip = Request::ip();
            $time = time();
            $sessionId = Session::getId();
            $uuid = Uuid::uuid4()->toString();
            Log::debug
            (
                sprintf
                (
                    "RequestScopedCache::getScopeId scope is empty ip %s time %s sessionId %s uuid%s .",
                    $ip , $time, $sessionId, $uuid
                )
            );

            $requestId = md5(sprintf("%s.%s.%s.%s", $ip, $time, $sessionId, $uuid));

            Log::debug
            (
                sprintf
                (
                    "RequestScopedCache::getScopeId setting request id %s.",
                    $requestId
                )
            );

            $request->headers->set('X-Request-ID', $requestId);

            $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

        }

        Log::debug(sprintf("RequestScopedCache::getScopeId retrieving request id %s" , $requestId));

        return $requestId;
    }

    /**
     * @param string $serializeName
     * @param int $id
     * @param string|null $expand
     * @param array $fields
     * @param array $relations
     * @return string
     */
    function getRequestKey(string $serializeName, int $id, ?string $expand = null, array $fields = [], array $relations = []):string{
        if(empty($expand))
            $expand = '';
        return md5($serializeName. $id.$expand.implode(',', $fields).implode(',', $relations));
    }
    /**
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    function cache(string $key, Closure $callback){

        $scope = self::getScopeId();

        Log::debug(sprintf("RequestScopedCache::cache scope %s key %s.", $scope, $key));

        $res = Cache::tags($scope)->get($key);
        if(!empty($res)){
            $json_res = gzinflate($res);
            $res = json_decode($json_res,true);
            Log::debug(sprintf("RequestScopedCache::cache scope %s key %s cache hit res %s.", $scope, $key, $json_res));
            return $res;
        }

        $res = $callback();
        $json = json_encode($res);
        Log::debug(sprintf("RequestScopedCache::cache scope %s key %s res %s adding to cache.", $scope, $key, $json));
        Cache::tags($scope)->add($key, gzdeflate($json, 9));
        return $res;
    }
}