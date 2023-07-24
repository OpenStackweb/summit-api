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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\Sponsor;

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
    ];

    protected static $allowed_relations = [
        'members',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $sponsor = $this->object;
        if (!$sponsor instanceof Sponsor) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('members', $relations)) {
            $members = [];
            foreach ($sponsor->getMembers() as $member) {
                $members[] = $member->getId();
            }
            $values['members'] = $members;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'summit':
                        {
                            $current_member = $params['member'];
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
                            if (!is_null($current_member && ($current_member->isAdmin() || $current_member->isSummitAdmin()))) {
                                $serializer_type = SerializerRegistry::SerializerType_Private;
                                $fields[] = 'qr_codes_enc_key';
                            }
                            unset($values['summit_id']);
                            $values['summit'] = SerializerRegistry::getInstance()
                                ->getSerializer($sponsor->getSummit(), $serializer_type)
                                ->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'summit'), $fields, ['none']);
                        }
                        break;
                    case 'members':
                        {
                            unset($values['members']);
                            $members = [];
                            foreach ($sponsor->getMembers() as $member) {
                                $members[] = SerializerRegistry::getInstance()->getSerializer($member)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'members'));
                            }
                            $values['members'] = $members;
                        }
                        break;
                    case 'company':
                        {
                            if ($sponsor->hasCompany()) {
                                unset($values['company_id']);
                                $values['company'] = SerializerRegistry::getInstance()->getSerializer($sponsor->getCompany())->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'company'));
                            }
                        }
                        break;
                    case 'sponsorship':
                        {
                            if ($sponsor->hasSponsorship()) {
                                unset($values['sponsorship_id']);
                                $values['sponsorship'] = SerializerRegistry::getInstance()->getSerializer($sponsor->getSponsorship())->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'sponsorship'));
                            }
                        }
                        break;
                    case 'featured_event':
                        {
                            if ($sponsor->hasFeaturedEvent()) {
                                unset($values['featured_event_id']);
                                $values['featured_event'] = SerializerRegistry::getInstance()->getSerializer($sponsor->getFeaturedEvent())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, 'featured_event'),
                                    AbstractSerializer::filterFieldsByPrefix($fields, 'featured_event')
                                );
                            }
                        }
                        break;
                }
            }
        }
        return $values;
    }
}