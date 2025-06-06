<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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

use models\exceptions\ValidationException;
use models\summit\Sponsor;
/**
 * Class SponsorFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorFactory
{
    /**
     * @param array $data
     * @return Sponsor
     */
    public static function build(array $data):Sponsor{
        return self::populate(new Sponsor, $data);
    }

    /**
     * @param Sponsor $sponsor
     * @param array $data
     * @return Sponsor
     * @throws ValidationException
     */
    public static function populate(Sponsor $sponsor, array $data):Sponsor{

        if(isset($data['company']))
            $sponsor->setCompany($data['company']);

        if(isset($data['sponsorship']))
            $sponsor->addSponsorship($data['sponsorship']);

        if(isset($data['featured_event']))
            $sponsor->setFeaturedEvent($data['featured_event']);

        if(isset($data['is_published']))
            $sponsor->setIsPublished(boolval($data['is_published']));

        if(isset($data['marquee']))
            $sponsor->setMarquee(trim($data['marquee']));

        if(isset($data['intro']))
            $sponsor->setIntro(trim($data['intro']));

        if(isset($data['external_link']))
            $sponsor->setExternalLink(trim($data['external_link']));

        if(isset($data['video_link']))
            $sponsor->setVideoLink(trim($data['video_link']));

        if(isset($data['chat_link']))
            $sponsor->setChatLink(trim($data['chat_link']));

        if(isset($data['side_image_alt_text']))
            $sponsor->setSideImageAltText(trim($data['side_image_alt_text']));

        if(isset($data['header_image_alt_text']))
            $sponsor->setHeaderImageAltText(trim($data['header_image_alt_text']));

        if(isset($data['header_image_mobile_alt_text']))
            $sponsor->setHeaderImageMobileAltText(trim($data['header_image_mobile_alt_text']));

        if(isset($data['carousel_advertise_image_alt_text']))
            $sponsor->setCarouselAdvertiseImageAltText(trim($data['carousel_advertise_image_alt_text']));

        if(isset($data['show_logo_in_event_page']))
            $sponsor->setShowLogoInEventPage(boolval($data['show_logo_in_event_page']));

        return $sponsor;
    }
}