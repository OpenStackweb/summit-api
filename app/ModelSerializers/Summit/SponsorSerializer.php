<?php namespace ModelSerializers;
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

use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\Summit;

/**
 * Class SponsorSerializer
 * @package ModelSerializers
 */
final class SponsorSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Order' => 'order:json_int',
        'SummitId' => 'summit_id:json_int',
        'CompanyId' => 'company_id:json_int',
        'SponsorshipId' => 'sponsorship_id:json_int',
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
     * @param Summit|null $summit
     * @param Sponsor $sponsor
     * @param Member $current_member
     * @return bool
     */
    private static function isQREncKeyFieldAllowed(?Summit $summit, Sponsor $sponsor,Member $current_member, ):bool {
        $is_member_authz = $current_member->isSponsorUser() || $current_member->isAdmin();
        Log::debug
        (
            sprintf
            (
                "SponsorSerializer::isQREncKeyFieldAllowed summit %s sponsor %s current member %s(%s) is_member_authz %b.",
                is_null($summit) ? 'N/A': $summit->getId(),
                $sponsor->getId(),
                $current_member->getEmail(),
                $current_member->getId(),
                $is_member_authz
            )
        );


        $res = $is_member_authz &&
            ( (!is_null($summit) && $current_member->isSummitAllowed($summit))
        || $current_member->hasSponsorMembershipsFor($sponsor->getSummit(), $sponsor) );

        Log::debug
        (
            sprintf
            (
                "SponsorSerializer::isQREncKeyFieldAllowed summit %s sponsor %s current member %s(%s) is_member_authz %b res %b.",
                is_null($summit) ? 'N/A': $summit->getId(),
                $sponsor->getId(),
                $current_member->getEmail(),
                $current_member->getId(),
                $is_member_authz,
                $res
            )
        );
        return $res;

    }

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

        if (in_array('extra_questions', $relations) && !isset($values['extra_questions'])) {
            $extra_questions = [];
            foreach ($sponsor->getExtraQuestions() as $extra_question) {
                $extra_questions[] = $extra_question->getId();
            }
            $values['extra_questions'] = $extra_questions;
        }

        if (in_array('members', $relations) && !isset($values['members'])) {
            $members = [];
            foreach ($sponsor->getMembers() as $member) {
                $members[] = $member->getId();
            }
            $values['members'] = $members;
        }

        if (in_array('sponsorships', $relations) && !isset($values['sponsorships'])) {
            $sponsorships = [];
            foreach ($sponsor->getSponsorships() as $sponsorship) {
                $sponsorships[] = $sponsorship->getId();
            }
            $values['sponsorships'] = $sponsorships;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                if ($relation == 'summit') {
                    {
                        $current_member = $params['member'] ?? null;
                        $summit = $params['summit'] ?? null;
                        $serializer_type = SerializerRegistry::SerializerType_Public;
                        $fields = [
                            'id',
                            'name',
                            'start_date',
                            'end_date',
                            'time_zone_id',
                            'order_qr_prefix',
                            'ticket_qr_prefix',
                            'badge_qr_prefix',
                            'qr_registry_field_delimiter'
                        ];
                        if ($current_member instanceof Member &&
                            ((!is_null($summit) && $current_member->isSummitAllowed($summit))
                                || $current_member->hasSponsorMembershipsFor($sponsor->getSummit(), $sponsor))) {
                            $serializer_type = SerializerRegistry::SerializerType_Private;
                            $fields[] = 'qr_codes_enc_key';
                        }
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()
                            ->getSerializer($sponsor->getSummit(), $serializer_type)
                            ->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'summit'), $fields, ['none']);
                    }
                }
            }
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