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

use models\summit\SponsorAd;


/**
 * Class SponsorAdFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorAdFactory
{
    /**
     * @param array $data
     * @return SponsorAd
     */
    public static function build(array $data):SponsorAd{
        return self::populate(new SponsorAd(), $data);
    }

    /**
     * @param SponsorAd $sponsor_ad
     * @param array $data
     * @return SponsorAd
     */
    public static function populate(SponsorAd $sponsor_ad, array $data):SponsorAd{
        if(isset($data['alt']))
            $sponsor_ad->setAlt(trim($data['alt']));
        if(isset($data['link']))
            $sponsor_ad->setLink(trim($data['link']));
        if(isset($data['text']))
            $sponsor_ad->setText(trim($data['text']));
        return $sponsor_ad;
    }
}