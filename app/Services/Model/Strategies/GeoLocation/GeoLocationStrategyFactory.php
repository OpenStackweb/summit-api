<?php namespace App\Services\Model\Strategies\GeoLocation;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\SummitGeoLocatedLocation;
/**
 * Class GeoLocationStrategyFactory
 * @package App\Services\Model\Strategies\GeoLocation
 */
final class GeoLocationStrategyFactory
{
    /**
     * @param SummitGeoLocatedLocation $location
     * @return IGeoLocationStrategy|null
     */
    public static function build(SummitGeoLocatedLocation $location):?IGeoLocationStrategy{
        $has_address = !empty($location->getAddress1()) &&
                !empty($location->getZipCode())&&
                !empty($location->getState()) &&
                !empty($location->getCity()) &&
                !empty($location->getCountry());
        $has_lat_lng = !empty($location->getLat()) && !empty($location->getLng());
        if ($has_address && !$has_lat_lng)
            return new GeoLocationAddressInfoStrategy();
        if($has_lat_lng && !$has_address)
            return new GeoLocationReverseStrategy();
        return null;
    }
}
