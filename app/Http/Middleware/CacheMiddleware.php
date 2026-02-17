<?php namespace App\Http\Middleware;
/**
 * Copyright 2026 OpenStack Foundation
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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libs\utils\CacheRegions;
use models\oauth2\IResourceServerContext;

final class CacheMiddleware
{
    private IResourceServerContext $context;

    public function __construct(IResourceServerContext $context)
    {
        $this->context = $context;
    }

    private const ENC_DF = 'DF1:';    // gzdeflate/gzinflate
    private const ENC_P0 = 'P0:';    // without compression
    private const LOCK_TTL = 10;      // lock auto-expires after 10s (safety net if holder crashes)
    private const LOCK_WAIT = 5;      // losers wait up to 5s for the winner to finish
    private int $gzipLevel = 9;

    private function encode(array $payload):string{
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $bin = gzdeflate($json, $this->gzipLevel);
        return $bin === false ? self::ENC_P0.$json : self::ENC_DF.$bin;
    }

    private function decode($value):?array{
        if (is_array($value)) return $value;     // back compat
        if (!is_string($value)) return null;

        if (str_starts_with($value, self::ENC_DF)) {
            Log::debug("CacheMiddleware::decode gzinflate");
            $bin  = substr($value, strlen(self::ENC_DF));
            $json = gzinflate($bin);
            if ($json === false) return null;
            $arr = json_decode($json, true);
            return is_array($arr) ? $arr : null;
        }

        if (str_starts_with($value, self::ENC_P0)) {
            Log::debug("CacheMiddleware::decode raw");
            $json = substr($value, strlen(self::ENC_P0));
            $arr = json_decode($json, true);
            if (is_array($arr)) return $arr;
        }

        // compat: JSON plano o serialize
        $arr = json_decode($value, true);
        if (is_array($arr)) return $arr;
        $un  = @unserialize($value);
        return is_array($un) ? $un : null;
    }
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure                   $next
     * @param  int                       $cache_lifetime    seconds
     * @param  string|null               $cache_region      one of CacheRegions::*
     * @param  string|null               $param_id          route parameter name (e.g. "event_id")
     * @return JsonResponse|mixed
     */
    public function handle($request, Closure $next, $cache_lifetime, $cache_region = null, $param_id = null)
    {

        // Only cache GETs:
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $cache_lifetime = intval($cache_lifetime);
        $key            = $this->buildKey($request);
        $regionTag      = null;

        // --- add per-request log context (agent, ip, etc.) ---
        $agent  = $request->userAgent() ?? 'unknown';
        // optional: avoid huge headers flooding logs
        $agent  = mb_substr($agent, 0, 300);
        $ip     = $request->ip();

        // If we have a region (e.g. summits/69 → CacheRegionEvents:69):
        if ($cache_region && $param_id) {
            $id = $request->route($param_id);
            if ($id) {
                $regionTag = CacheRegions::getCacheRegionFor($cache_region, $id);
            }
        }
        $cache = $regionTag ? Cache::tags($regionTag) : Cache::store();
        $logCtx = array_filter([
            'tag' => $regionTag,
            'ip' => $ip,
            'agent' => $agent,
            'key' => $key,
        ]);

        // Phase 1: optimistic read — no lock needed on hit
        $encoded = $cache->get($key);

        if ($encoded !== null) {
            Log::debug("CacheMiddleware: cache HIT", $logCtx);

            $data = $this->decode($encoded);
            if ($data === null) $data = is_array($encoded) ? $encoded : [];

            $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json']);
            $wasHit = true;
        } else {
            // Phase 2: cache miss — acquire lock so only one request executes the handler
            $lockKey = $regionTag ? "cache_lock:{$regionTag}:{$key}" : "cache_lock:{$key}";
            $lock = Cache::lock($lockKey, self::LOCK_TTL);
            $wasHit = false;

            try {
                if ($lock->block(self::LOCK_WAIT)) {
                    // Won the lock — double-check: another request may have populated the cache
                    $encoded = $cache->get($key);

                    if ($encoded !== null) {
                        Log::debug("CacheMiddleware: cache HIT (after lock)", $logCtx);

                        $data = $this->decode($encoded);
                        if ($data === null) $data = is_array($encoded) ? $encoded : [];

                        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json']);
                        $wasHit = true;
                    } else {
                        Log::debug("CacheMiddleware: cache MISS (executing handler)", $logCtx);

                        $resp = $next($request);

                        // Only cache 200 JSON responses; let everything else pass through as-is
                        if ($resp instanceof JsonResponse && $resp->getStatusCode() === 200) {
                            $cache->put($key, $this->encode($resp->getData(true)), $cache_lifetime);
                        } else {
                            return $resp;
                        }

                        $response = $resp;
                    }
                } else {
                    // Could not acquire lock within LOCK_WAIT seconds — fall through without lock
                    Log::warning("CacheMiddleware: lock timeout, executing handler without lock", $logCtx);

                    $resp = $next($request);

                    if ($resp instanceof JsonResponse && $resp->getStatusCode() === 200) {
                        $cache->put($key, $this->encode($resp->getData(true)), $cache_lifetime);
                    } else {
                        return $resp;
                    }

                    $response = $resp;
                }
            } finally {
                $lock->release();
            }
        }

        // Mark for revalidation so ETag middleware can return 304 when unchanged
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('proxy-revalidate', true);
        $response->headers->add([
            'X-Cache-Result' => $wasHit ? 'HIT' : 'MISS',
        ]);

        Log::debug("CacheMiddleware: returning response", $logCtx);

        return $response;
    }

    /**
     * Build a cache key based on path + sorted query params
     */
    private function buildKey($request): string
    {
        $path   = $request->getPathInfo();
        $csvKeys = ['fields','expand','relations'];

        // apply patch only when request is /api/v1/summits/{id} or  /api/public/v1/summits/{id}
        $applyTracksCompat = (preg_match('#^/api/(public/)?v1/summits/\d+/?$#', $path) === 1);

        $params = collect($request->query())
            ->except(['access_token','token_type','q','t','evict_cache'])
            ->sortKeys()
            ->map(function($v, $k) use ($csvKeys, $applyTracksCompat, $path) {
                $str = is_array($v) ? implode(',', $v) : (string)$v;
                if (in_array($k, $csvKeys, true)) {
                    // "a, b ,  c" -> "a,b,c"
                    $items = preg_split('/\s*,\s*/', trim($str), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                    if($k ==='fields' && $applyTracksCompat){
                        $set = array_flip($items);
                        if (!isset($set['dates_label'])) {
                            Log::warning(sprintf("CacheMiddleware: normalizing fields for path %s", $path));
                            $set['dates_label'] = true;
                        }
                        $items = array_keys($set);
                    }
                    else if ($k === 'relations' && $applyTracksCompat) {
                        $set = array_flip($items);
                        /**
                         * legacy: tracks,tracks.subtracks.none
                         * new: tracks,tracks.subtracks,tracks.subtracks.none
                         */
                        if (isset($set['tracks']) &&
                            !isset($set['tracks.subtracks'])
                            && isset($set['tracks.subtracks.none'])) {
                            Log::warning(sprintf("CacheMiddleware: normalizing relations for path %s", $path));
                            $set['tracks.subtracks'] = true;
                        }
                        $items = array_keys($set);
                    } else {
                        $items = array_values(array_unique($items));
                    }

                    sort($items, SORT_STRING);
                    $str = implode(',', $items);
                }
                return $str;
            })
            ->all();

        if (str_contains($path, '/me') && $user = $this->context->getCurrentUser()) {
            // per-user cache on /me routes
            $path .= ":{$user->getId()}";
        }

        if (empty($params)) {
            return $path;
        }

        // build a normalized query string
        $qs = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return sha1("{$path}.{$qs}");
    }
}
