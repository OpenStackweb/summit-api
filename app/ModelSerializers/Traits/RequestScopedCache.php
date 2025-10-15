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

    static function store(){
        return  Cache::store(config('cache.request_scope_cache_store', 'array'));
    }
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
            $requestId = md5(sprintf("%s.%s.%s.%s", $ip, $time, $sessionId, $uuid));
            $request->headers->set('X-Request-ID', $requestId);
            $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

        }

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
        $key   = self::getScopeId().':'.$key;
        $store = self::store();

        $computed = false;

        $value = $store->remember($key, now()->addSeconds(30), function () use ($callback, &$computed, $key) {
            $computed = true; // closure ran => MISS
            Log::debug('RequestScopedCache MISS', ['key' => $key]);
            return $callback();
        });

        if (!$computed) {
            Log::debug('RequestScopedCache HIT', ['key' => $key]);
        }

        return $value;
    }
}
