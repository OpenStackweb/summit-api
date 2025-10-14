<?php namespace App\Utils\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class MemCache
{
    private const CM_REGION = 'cm:region:';

    private static function store(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store('memcached');
    }

    public static function get(string $key)
    {
        try {
            Log::debug("MemCacheService::get", ["key" => $key]);
            return self::store()->get($key, null);
        } catch (\Throwable $e) {
            Log::warning($e);
            return null;
        }
    }


    private static function trackKey(string $regionTag, string $key, int $ttl): void
    {
        try {
            $store = self::store();
            Log::debug("MemCache::trackKey", ["regionTag" => $regionTag, "key" => $key, "ttl" => $ttl]);
            $setKey = self::CM_REGION.$regionTag;
            $list = $store->get($setKey, []);
            if (!\is_array($list)) $list = [];
            $list[$key] = \time() + $ttl;
            if (\count($list) > 10000) { array_shift($list); }
            $store->put($setKey, $list, $ttl);
        } catch (\Throwable $e) {
            Log::warning($e);
        }
    }

    public static function put(string $key, $value, int $ttl, ?string $regionTag = null): void
    {
        try {
            Log::debug("MemCache::put", ["key" => $key, "value" => $value, "ttl" => $ttl]);
            self::store()->put($key, $value, $ttl);
            if ($regionTag) self::trackKey($regionTag, $key, $ttl);
        } catch (\Throwable $e) {
            Log::warning($e);
        }
    }

    public static function apcClearRegion(string $regionTag): int
    {
        try {
            $store = self::store();
            $setKey = self::CM_REGION.$regionTag;
            $list = $store->get($setKey, []);
            $n = 0;
            Log::debug("MemCache::apcClearRegion", ["regionTag" => $regionTag, "list" => $list]);
            if (\is_array($list)) {
                foreach (array_keys($list) as $k) {
                    if ($store->forget($k)) $n++;
                }
            }
            $store->forget($setKey);
            return $n;
        } catch (\Throwable $e) {
            Log::warning($e);
            return 0;
        }
    }

}
