<?php namespace App\Http\Middleware;
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Support\Facades\Config;
use Illuminate\Http\JsonResponse;
use libs\utils\CacheRegions;
use libs\utils\ICacheService;
use Illuminate\Support\Facades\Log;
use models\oauth2\IResourceServerContext;

/**
 * Class CacheMiddleware
 * @package App\Http\Middleware
 */
final class CacheMiddleware
{
    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @var IResourceServerContext
     */
    private $context;

    public function __construct(IResourceServerContext $context, ICacheService $cache_service)
    {
        $this->context = $context;
        $this->cache_service = $cache_service;
    }


    /**
     * @param $request
     * @param Closure $next
     * @param $cache_lifetime
     * @param null $cache_region
     * @param null $param_id
     * @return JsonResponse|mixed
     */
    public function handle($request, Closure $next, $cache_lifetime, $cache_region = null, $param_id = null)
    {
        Log::debug('CacheMiddleware::handle');
        $cache_lifetime = intval($cache_lifetime);
        if ($request->getMethod() !== 'GET') {
            // short circuit
            Log::debug('CacheMiddleware::handle method is not GET');
            return $next($request);
        }

        $key = $request->getPathInfo();
        $query = $request->getQueryString();
        $current_time = time();
        $evict_cache = false;
        if (!empty($query)) {
            Log::debug(sprintf('CacheMiddleware::handle query %s', $query));
            $query = explode('&', $query);
            foreach ($query as $q) {
                $q = explode('=', $q);
                /*
                if(strtolower($q[0]) === "evict_cache"){
                    if(strtolower($q[1]) === '1') {
                        Log::debug('CacheMiddleware::handle cache will be evicted');
                        $evict_cache = true;
                    }
                    continue;
                }
                */
                if(in_array(strtolower($q[0]), ['access_token', 'token_type', 'q', 't','evict_cache'])) continue;
                $key .= "." . implode("=", $q);
            }
        }

        if (str_contains($request->getPathInfo(), '/me')) {
            $current_member = $this->context->getCurrentUser();
            if (!is_null($current_member))
                $key .= ':' . $current_member->getId();
        }

        $data = $this->cache_service->getSingleValue($key);
        $time = $this->cache_service->getSingleValue($key . ".generated");
        $region = [];
        $cache_region_key = null;
        if(!empty($cache_region) && !empty($param_id)){
            $id = $request->route($param_id);
            $cache_region_key = CacheRegions::getCacheRegionFor($cache_region, $id);
            if(!empty($cache_region_key) && $this->cache_service->exists($cache_region_key)) {
                //
                Log::debug(sprintf("CacheMiddleware::handle trying to get region %s data ...", $cache_region_key));
                $region_data = $this->cache_service->getSingleValue($cache_region_key);
                if(!empty($region_data)){
                    $region = json_decode(gzinflate($region_data), true);
                    Log::debug(sprintf("CacheMiddleware::handle got payload %s for region %s", json_encode($region), $cache_region_key));
                }
            }
        }
        if (empty($data) || empty($time) || $evict_cache) {
            $time = $current_time;
            Log::debug(sprintf("CacheMiddleware::handle cache value not found for key %s , getting from api...", $key));
            // normal flow ...
            $response = $next($request);
            if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
                // and if its json, store it on cache ..).
                $data = $response->getData(true);
                Log::debug(sprintf("CacheMiddleware::handle storing data for key %s", $key));
                $this->cache_service->setSingleValue($key, gzdeflate(json_encode($data), 9), $cache_lifetime);
                $this->cache_service->setSingleValue($key . ".generated", $time, $cache_lifetime);
                if(!empty($cache_region_key)){
                    $region[$key] = $key;
                    Log::debug(sprintf("CacheMiddleware::handle storing data for region %s", $cache_region_key));
                    $this->cache_service->setSingleValue($cache_region_key, gzdeflate(json_encode($region), 9));
                }
            }
        } else {
            $ttl = $this->cache_service->ttl($key);
            // cache hit ...
            Log::debug(sprintf("CacheMiddleware::handle cache hit for %s - ttl %s ...", $key, $ttl));
            $response = new JsonResponse(json_decode(gzinflate($data), true), 200, [
                    'content-type' => 'application/json',
                ]
            );
        }
        /**
         * CacheMiddleware marks responses with
         * Cache-Control: public, max-age=0, must-revalidate
         * so browsers always re-validate rather than blindly re-use a stale body.
         */
        // 1) mark it cacheable...
        $response->setPublic();

        // 2) immediately stale, but revalidate
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('proxy-revalidate', true);

        return $response;
    }
}