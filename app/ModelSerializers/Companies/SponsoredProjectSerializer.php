<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\Many2OneExpandSerializer;
/**
 * Class SponsoredProjectSerializer
 * @package ModelSerializers
 */
final class SponsoredProjectSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Description' => 'description:json_string',
        'Slug' => 'slug:json_string',
        'Active' => 'is_active:json_boolean',
        'NavBarTitle' => 'nav_bar_title:json_string',
        'ShouldShowOnNavBar' => 'should_show_on_nav_bar:json_boolean',
        'LearnMoreLink' => 'learn_more_link:json_url',
        'LearnMoreText' => 'learn_more_text:json_string',
        'SiteURL' => 'site_url:json_url',
        'LogoUrl' => 'logo_url:json_url',
        'SponsorshipTypesIds' => 'sponsorship_types',
    ];

    protected static $expand_mappings = [
        'sponsorship_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSponsorshipTypes',
        ]
    ];
}