<?php namespace App\ModelSerializers\Summit;
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\Sponsor;

/**
 * Class SponsorSerializerV2
 * @package ModelSerializers
 */
final class SponsorSerializerV2 extends SponsorBaseSerializer
{
    protected static $array_mappings = [
        'Order' => 'order:json_int',
        'SummitId' => 'summit_id:json_int',
        'CompanyId' => 'company_id:json_int',
        'Published' => 'is_published:json_boolean',
        'SideImageUrl' => 'side_image:json_url',
        'HeaderImageUrl' => 'header_image:json_url',
        'HeaderImageMobileUrl' => 'header_image_mobile:json_url',
        'CarouselAdvertiseImageUrl' => 'carousel_advertise_image:json_url',
        'Marquee' => 'marquee:json_string',
        'Intro' => 'intro:json_string',
        'ExternalLink' => 'external_link:json_string',
        'VideoLink' => 'video_link:json_string',
        'ChatLink' => 'chat_link:json_string',
        'FeaturedEventId' => 'featured_event_id:json_int',
        'HeaderImageAltText' => 'header_image_alt_text:json_string',
        'SideImageAltText'   => 'side_image_alt_text:json_string',
        'HeaderImageMobileAltText' => 'header_image_mobile_alt_text:json_string',
        'CarouselAdvertiseImageAltText' => 'carousel_advertise_image_alt_text:json_string',
        'ShowLogoInEventPage' => 'show_logo_in_event_page:json_boolean',
        'LeadReportSettingId' => 'lead_report_setting_id:json_int',
    ];

    protected static $allowed_relations = [
        'extra_questions',
        'members',
        'sponsorships',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $sponsor = $this->object;
        if (!$sponsor instanceof Sponsor) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('sponsorships', $relations) && !isset($values['sponsorships'])) {
            $sponsorships = [];
            foreach ($sponsor->getSponsorships() as $sponsorship) {
                $sponsorships[] = $sponsorship->getId();
            }
            $values['sponsorships'] = $sponsorships;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'extra_questions' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getExtraQuestions',
        ],
        'members' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getMembers',
        ],
        'sponsorships' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSponsorships',
        ],
        'company' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'company_id',
            'getter' => 'getCompany',
            'has' => 'hasCompany'
        ],
        'featured_event' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'featured_event_id',
            'getter' => 'getFeaturedEvent',
            'has' => 'hasFeaturedEvent'
        ],
        'lead_report_setting' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'lead_report_setting_id',
            'getter' => 'getLeadReportSetting',
            'has' => 'hasLeadReportSetting'
        ],
    ];
}