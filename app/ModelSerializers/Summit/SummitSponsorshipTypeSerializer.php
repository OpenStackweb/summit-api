<?php namespace App\ModelSerializers\Summit;
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

use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitSponsorshipType
 * @package App\ModelSerializers\Summit
 */
final class SummitSponsorshipTypeSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'WidgetTitle' => 'widget_title:json_string',
        'LobbyTemplate' => 'lobby_template:json_string',
        'ExpoHallTemplate' => 'expo_hall_template:json_string',
        'SponsorPageTemplate' => 'sponsor_page_template:json_string',
        'EventPageTemplate' => 'event_page_template:json_string',
        'SponsorPageUseDisqusWidget' => 'sponsor_page_use_disqus_widget:json_boolean',
        'SponsorPageUseLiveEventWidget' => 'sponsor_page_use_live_event_widget:json_boolean',
        'SponsorPageUseScheduleWidget' => 'sponsor_page_use_schedule_widget:json_boolean',
        'SponsorPageUseBannerWidget' => 'sponsor_page_use_banner_widget:json_boolean',
        'TypeId' => 'type_id:json_int',
        'BadgeImageUrl'  => 'badge_image:json_string',
        'BadgeImageAltText'  => 'badge_image_alt_text:json_string',
        'SummitID' => 'summit_id:json_int',
        'Order' => 'order:json_int',
        'ShouldDisplayOnExpoHallPage' => 'should_display_on_expo_hall_page:json_boolean',
        'ShouldDisplayOnLobbyPage' => 'should_display_on_lobby_page:json_boolean',
    ];

    protected static $expand_mappings = [
        'summit' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'summit_id',
            'getter' => 'getSummit',
            'has' => 'hasSummit'
        ],
        'type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'type_id',
            'getter' => 'getType',
            'has' => 'hasType'
        ],
    ];
}