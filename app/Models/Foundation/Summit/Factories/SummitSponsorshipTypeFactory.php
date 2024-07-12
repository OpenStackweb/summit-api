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

use models\summit\SummitSponsorshipType;

/**
 * Class SummitSponsorshipTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitSponsorshipTypeFactory {
  /**
   * @param array $payload
   * @return SummitSponsorshipType
   */
  public static function build(array $payload): SummitSponsorshipType {
    return self::populate(new SummitSponsorshipType(), $payload);
  }

  /**
   * @param SummitSponsorshipType $type
   * @param array $payload
   * @return SummitSponsorshipType
   */
  public static function populate(
    SummitSponsorshipType $type,
    array $payload,
  ): SummitSponsorshipType {
    if (isset($payload["widget_title"])) {
      $type->setWidgetTitle(trim($payload["widget_title"]));
    }

    if (isset($payload["lobby_template"])) {
      $type->setLobbyTemplate(trim($payload["lobby_template"]));
    }

    if (isset($payload["expo_hall_template"])) {
      $type->setExpoHallTemplate(trim($payload["expo_hall_template"]));
    }

    if (isset($payload["sponsor_page_template"])) {
      $type->setSponsorPageTemplate(trim($payload["sponsor_page_template"]));
    }

    if (isset($payload["event_page_template"])) {
      $type->setEventPageTemplate(trim($payload["event_page_template"]));
    }

    if (isset($payload["sponsor_page_use_disqus_widget"])) {
      $type->setSponsorPageUseDisqusWidget(boolval($payload["sponsor_page_use_disqus_widget"]));
    }

    if (isset($payload["sponsor_page_use_live_event_widget"])) {
      $type->setSponsorPageUseLiveEventWidget(
        boolval($payload["sponsor_page_use_live_event_widget"]),
      );
    }

    if (isset($payload["sponsor_page_use_schedule_widget"])) {
      $type->setSponsorPageUseScheduleWidget(boolval($payload["sponsor_page_use_schedule_widget"]));
    }

    if (isset($payload["sponsor_page_use_banner_widget"])) {
      $type->setSponsorPageUseBannerWidget(boolval($payload["sponsor_page_use_banner_widget"]));
    }

    if (isset($payload["badge_image_alt_text"])) {
      $type->setBadgeImageAltText(trim($payload["badge_image_alt_text"]));
    }

    if (isset($payload["should_display_on_lobby_page"])) {
      $type->setShouldDisplayOnLobbyPage(boolval($payload["should_display_on_lobby_page"]));
    }

    if (isset($payload["should_display_on_expo_hall_page"])) {
      $type->setShouldDisplayOnExpoHallPage(boolval($payload["should_display_on_expo_hall_page"]));
    }

    return $type;
  }
}
