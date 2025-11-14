<?php namespace App\Http\Middleware;

use App\Utils\Cache\MemCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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

    private const ENC_DF = 'DF1:';  // gzdeflate/gzinflate
    private const ENC_P0 = 'P0:';   // without compression
    private int $gzipLevel = 9;           //

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
        $status = 200;
        $wasMemCacheHit = false;
        $wasHit = false;
        $data = null;

        if ($regionTag) {
            Log::debug("CacheMiddleware: using region tag {$regionTag} ip {$ip} agent {$agent}");
            // try L1
            $encoded = MemCache::get($key);
            $wasMemCacheHit = $encoded !== null;
            if($wasMemCacheHit){
                Log::debug("CacheMiddleware:: MemcCache Hit");
            }
            if(!$wasMemCacheHit) {
                // then L2 Redis
                $wasHit = Cache::tags($regionTag)->has($key);
                Log::debug($wasHit ? "CacheMiddleware: cache HIT Redis (tagged)" : "CacheMiddleware: cache MISS (tagged)", [
                    'tag' => $regionTag,
                    'ip' => $ip,
                    'agent' => $agent,
                    'key' => $key,
                ]);

                $encoded = Cache::tags($regionTag)
                    ->remember($key, $cache_lifetime, function () use ($next, $request, $regionTag, $key, $cache_lifetime, &$status, $ip, $agent) {
                        $resp = $next($request);
                        if ($resp instanceof JsonResponse) {
                            $status = $resp->getStatusCode();
                            if ($status === 200) {
                                return $this->encode($resp->getData(true));
                            }
                        }
                        // don’t cache non-200 or non-JSON
                        return Cache::get($key);
                    });


                // backfill Memcache only if we actually have a value
                if ($encoded !== null) { // avoid null writes
                    MemCache::put($key, $encoded, $cache_lifetime, $regionTag);
                }
            }
            $data = $this->decode($encoded);
        } else {
            // try L1
            $encoded = MemCache::get($key);
            $wasMemCacheHit = !is_null($encoded);
            if($wasMemCacheHit){
                Log::debug("CacheMiddleware:: MemcCache Hit");
            }
            if(!$wasMemCacheHit) {
                // then L2 Redis

                $wasHit = Cache::has($key);

                Log::debug($wasHit ? "CacheMiddleware: cache HIT" : "CacheMiddleware: cache MISS", [
                    'ip' => $ip,
                    'agent' => $agent,
                    'key' => $key,
                ]);

                $encoded = Cache::remember($key, $cache_lifetime, function () use ($next, $request, $key, &$status, $ip, $agent) {
                    $resp = $next($request);
                    if ($resp instanceof JsonResponse) {
                        $status = $resp->getStatusCode();
                        if ($status === 200)
                            return $this->encode($resp->getData(true));
                    }
                    return Cache::get($key);
                });
                // store at MemCache
                if ($encoded !== null) { // avoid null writes
                    MemCache::put($key, $encoded, $cache_lifetime);
                }

                $data = $this->decode($encoded);
            }
        }
        // safe guard
        if ($data === null) $data = is_array($encoded) ? $encoded : [];

        // Build the JsonResponse (either from cache or fresh)
        $response = new JsonResponse($data, $status, ['Content-Type' => 'application/json']);

        // Mark for revalidation so your ETag middleware can return 304 when unchanged
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('proxy-revalidate', true);
        $response->headers->add([
            'X-Cache-Result' => ($wasMemCacheHit || $wasHit) ? 'HIT' : 'MISS',
            'X-Cache-Type' =>  $wasMemCacheHit ? 'M' : ($wasHit ? 'R' : 'N'),
        ]);
        Log::debug( "CacheMiddleware: returning response", [
            'ip' => $ip,
            'agent' => $agent,
            'key' => $key,
        ]);

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
