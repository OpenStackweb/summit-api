<?php namespace App\ModelSerializers\Summit;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\Summit;
use models\summit\SummitScheduleConfig;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SummitSerializer;

/**
 * Class AdminSummitSerializer
 * @package App\ModelSerializers\Summit
 */
final class AdminSummitSerializer extends SummitSerializer {
  protected static $array_mappings = [
    "AvailableOnApi" => "available_on_api:json_boolean",
    "MaxSubmissionAllowedPerUser" => "max_submission_allowed_per_user:json_int",
    "RegistrationLink" => "registration_link:json_string",
    "Link" => "link:json_string",
    "ExternalSummitId" => "external_summit_id:json_string",
    "CalendarSyncName" => "calendar_sync_name:json_string",
    "CalendarSyncDesc" => "calendar_sync_desc:json_string",
    // External Feeds
    "ApiFeedType" => "api_feed_type:json_string",
    "ApiFeedUrl" => "api_feed_url:json_string",
    "ApiFeedKey" => "api_feed_key:json_string",
    // registration
    "OrderQRPrefix" => "order_qr_prefix:json_string",
    "TicketQRPrefix" => "ticket_qr_prefix:json_string",
    "BadgeQRPrefix" => "badge_qr_prefix:json_string",
    "QRRegistryFieldDelimiter" => "qr_registry_field_delimiter:json_string",
    "QRCodesEncKey" => "qr_codes_enc_key:json_string",
    "ReassignTicketTillDate" => "reassign_ticket_till_date:datetime_epoch",
    "RegistrationDisclaimerContent" => "registration_disclaimer_content:json_string",
    "RegistrationDisclaimerMandatory" => "registration_disclaimer_mandatory:json_boolean",
    // registration external feed
    "ExternalRegistrationFeedType" => "external_registration_feed_type:json_string",
    "ExternalRegistrationFeedApiKey" => "external_registration_feed_api_key:json_string",
    // oauth2 clients
    "VirtualSiteOAuth2ClientId" => "virtual_site_oauth2_client_id:json_string",
    "MarketingSiteOAuth2ClientId" => "marketing_site_oauth2_client_id:json_string",
    // calculated attributes
    "PresentationVotesCount" => "presentation_votes_count:json_int",
    "PresentationVotersCount" => "presentation_voters_count:json_int",
    "PresentationsSubmittedCount" => "presentations_submitted_count:json_int",
    "AttendeesCount" => "attendees_count:json_int",
    "PaidTicketsCount" => "paid_tickets_count:json_int",
    "SpeakersCount" => "speakers_count:json_int",
    "SpeakerAnnouncementEmailAcceptedCount" => "speaker_announcement_email_accepted_count:json_int",
    "SpeakerAnnouncementEmailRejectedCount" => "speaker_announcement_email_rejected_count:json_int",
    "SpeakerAnnouncementEmailAlternateCount" =>
      "speaker_announcement_email_alternate_count:json_int",
    "SpeakerAnnouncementEmailAcceptedAlternateCount" =>
      "speaker_announcement_email_accepted_alternate_count:json_int",
    "SpeakerAnnouncementEmailAcceptedRejectedCount" =>
      "speaker_announcement_email_accepted_rejected_count:json_int",
    "SpeakerAnnouncementEmailAlternateRejectedCount" =>
      "speaker_announcement_email_alternate_rejected_count:json_int",
    // MUX
    "MuxTokenId" => "mux_token_id:json_string",
    "MuxTokenSecret" => "mux_token_secret:json_string",
    "MuxAllowedDomains" => "mux_allowed_domains:json_string_array",
  ];

  protected static $allowed_relations = [
    "ticket_types",
    "locations",
    "wifi_connections",
    "selection_plans",
    "meeting_booking_room_allowed_attributes",
    "summit_sponsors",
    "order_extra_questions",
    "tax_types",
    "payment_profiles",
    "email_flows_events",
    "summit_documents",
    "featured_speakers",
    "dates_with_events",
    "presentation_action_types",
    "track_groups",
  ];

  /**
   * @param string|null $relation
   * @return string
   */
  protected function getSerializerType(?string $relation = null): string {
    return SerializerRegistry::SerializerType_Private;
  }

  /**
   * @param null $expand
   * @param array $fields
   * @param array $relation
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $summit = $this->object;
    if (!$summit instanceof Summit) {
      return [];
    }
    $values = parent::serialize($expand, $fields, $relations, $params);

    if (in_array("track_groups", $relations) && !isset($values["track_groups"])) {
      $track_groups = [];
      foreach ($summit->getCategoryGroups() as $group) {
        $track_groups[] = $group->getId();
      }
      $values["track_groups"] = $track_groups;
    }

    return $values;
  }

  protected static $expand_mappings = [
    "track_groups" => [
      "type" => Many2OneExpandSerializer::class,
      "getter" => "getCategoryGroups",
    ],
  ];
}
