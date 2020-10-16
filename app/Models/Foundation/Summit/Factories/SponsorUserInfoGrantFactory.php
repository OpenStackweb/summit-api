<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\SponsorBadgeScan;
use models\summit\SponsorUserInfoGrant;
/**
 * Class SponsorUserInfoGrant
 * @package App\Models\Foundation\Summit\SponsorUserInfoGrantFactory
 */
final class SponsorUserInfoGrantFactory
{
    /**
     * @param array $payload
     * @return SponsorUserInfoGrant
     */
    public static function build(array $payload):SponsorUserInfoGrant {
        $grant = null;
        $class_name = $payload['class_name'];
        switch($class_name){
            case SponsorUserInfoGrant::ClassName:
                $grant = self::populate(new SponsorUserInfoGrant, $payload);
                break;
            case SponsorBadgeScan::ClassName:
                $grant = self::populate(new SponsorBadgeScan, $payload);
                break;
        }
        return $grant;
    }

    /**
     * @param SponsorUserInfoGrant $grant
     * @param array $payload
     * @return SponsorUserInfoGrant
     */
    public static function populate(SponsorUserInfoGrant $grant, array $payload):SponsorUserInfoGrant{
        $class_name = $payload['class_name'];
        switch($class_name){
            case SponsorUserInfoGrant::ClassName:
                break;
            case SponsorBadgeScan::ClassName:

                break;
        }
        return $grant;
    }
}