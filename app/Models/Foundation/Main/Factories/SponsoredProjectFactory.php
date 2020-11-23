<?php namespace App\Models\Foundation\Main\Factories;
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
use models\main\SponsoredProject;

/**
 * Class SponsoredProjectFactory
 * @package App\Models\Foundation\Main\Factories
 */
final class SponsoredProjectFactory
{
    /**
     * @param array $payload
     * @return SponsoredProject
     */
    public static function build(array $payload):SponsoredProject{

        return self::populate(new SponsoredProject,$payload);
    }

    /**
     * @param SponsoredProject $sponsoredProject
     * @param array $payload
     * @return SponsoredProject
     */
    public static function populate(SponsoredProject $sponsoredProject, array $payload):SponsoredProject{

        if(isset($payload['name']))
            $sponsoredProject->setName(trim($payload['name']));

        if(isset($payload['description']))
            $sponsoredProject->setDescription(trim($payload['description']));

        if(isset($payload['is_active']))
            $sponsoredProject->setIsActive(boolval($payload['is_active']));

        return  $sponsoredProject;
    }
}