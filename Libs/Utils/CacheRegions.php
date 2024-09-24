<?php namespace libs\utils;

/*
 * Copyright 2023 OpenStack Foundation
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

/**
 * Class CacheRegions
 * @package libs\utils
 */
final class CacheRegions
{

    const CacheRegionEvents = 'EVENTS';


    const CacheRegionSummits = 'SUMMITS';
    const CacheRegionEventsKey = "CACHE_REGION_SUMMIT_EVENT_%s";

    const CacheRegionSummitsKey = "CACHE_REGION_SUMMIT_%s";

    /**
     * @param string $region
     * @param int $entity_id
     * @return string|null
     */
    public static function getCacheRegionFor(string $region, int $entity_id):?string{
        switch($region){
            case CacheRegions::CacheRegionEvents:
                return self::getCacheRegionForSummitEvent($entity_id);
            case CacheRegions::CacheRegionSummits:
                return self::getCacheRegionForSummit($entity_id);
        }
        return null;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getCacheRegionForSummitEvent(int $id):string{
        return sprintf(CacheRegions::CacheRegionEventsKey, $id);
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getCacheRegionForSummit(int $id):string{
        return sprintf(CacheRegions::CacheRegionSummitsKey, $id);
    }
}