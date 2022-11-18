<?php namespace App\Http\Controllers;
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
use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use models\summit\SummitSponsorshipType;

/**
 * Class SummitSponsorshipTypeValidationRules
 * @package App\Http\Controllers
 */
final class SummitSponsorshipTypeValidationRules extends AbstractValidationRulesFactory
{

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'widget_title' => 'sometimes|nullable|string|max:255',
            'lobby_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidLobbyTemplates),
            'expo_hall_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidExpoHallTemplates),
            'sponsor_page_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidSponsorPageTemplates),
            'event_page_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidEventPageTemplates),
            'sponsor_page_use_disqus_widget' => 'sometimes|boolean',
            'sponsor_page_use_live_event_widget' => 'sometimes|boolean',
            'sponsor_page_use_schedule_widget' => 'sometimes|boolean',
            'sponsor_page_use_banner_widget' => 'sometimes|boolean',
            'type_id' => 'required|integer',
            'badge_image_alt_text' => 'sometimes|nullable|string|max:255',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'widget_title' => 'sometimes|nullable|string|max:255',
            'lobby_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidLobbyTemplates),
            'expo_hall_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidExpoHallTemplates),
            'sponsor_page_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidSponsorPageTemplates),
            'event_page_template' => 'sometimes|nullable|string|in:'.join(',', SummitSponsorshipType::ValidEventPageTemplates),
            'sponsor_page_use_disqus_widget' => 'sometimes|boolean',
            'sponsor_page_use_live_event_widget' => 'sometimes|boolean',
            'sponsor_page_use_schedule_widget' => 'sometimes|boolean',
            'sponsor_page_use_banner_widget' => 'sometimes|boolean',
            'type_id' => 'sometimes|integer',
            'order' => 'sometimes|integer|min:1',
            'badge_image_alt_text' => 'sometimes|nullable|string|max:255',
        ];
    }
}