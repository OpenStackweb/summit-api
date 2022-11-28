<?php namespace App\Models\Foundation\Summit\Factories;
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

use models\summit\SponsorSocialNetwork;

/**
 * Class SponsorSocialNetworkFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorSocialNetworkFactory
{
    /**
     * @param array $data
     * @return SponsorSocialNetwork
     */
    public static function build(array $data):SponsorSocialNetwork{
        return self::populate(new SponsorSocialNetwork(), $data);
    }

    /**
     * @param SponsorSocialNetwork $social_network
     * @param array $data
     * @return SponsorSocialNetwork
     */
    public static function populate(SponsorSocialNetwork $social_network, array $data):SponsorSocialNetwork{
        if(isset($data['link']))
            $social_network->setLink(trim($data['link']));
        if(isset($data['icon_css_class']))
            $social_network->setIconCssClass(trim($data['icon_css_class']));
        if (isset($data['is_enabled'])) {
            $social_network->setIsEnabled(boolval($data['is_enabled']));
        }
        return $social_network;
    }
}