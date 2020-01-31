<?php namespace App\Security;
/**
 * Copyright 2017 OpenStack Foundation
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

/**
 * Class SummitScopes
 * @package App\Security
 */
final class SummitScopes
{
    const ReadSummitData                      = '%s/summits/read';
    const ReadAllSummitData                   = '%s/summits/read/all';

    // registration
    const CreateRegistrationOrders            = '%s/summits/registration-orders/create';
    const CreateOfflineRegistrationOrders     = '%s/summits/registration-orders/create/offline';
    const UpdateRegistrationOrders            = '%s/summits/registration-orders/update';
    const UpdateRegistrationOrdersBadges      = '%s/summits/registration-orders/badges/update';
    const PrintRegistrationOrdersBadges       = '%s/summits/registration-orders/badges/print';
    const UpdateMyRegistrationOrders          = '%s/summits/registration-orders/update/me';
    const DeleteRegistrationOrders            = '%s/summits/registration-orders/delete';
    const DeleteMyRegistrationOrders          = '%s/summits/registration-orders/delete/me';
    const ReadMyRegistrationOrders            = '%s/summits/registration-orders/read/me';
    const ReadRegistrationOrders              = '%s/summits/registration-orders/read';
    const WriteBadgeScan                      = '%s/summits/badge-scans/write';
    const ReadBadgeScan                       = '%s/summits/badge-scans/read';
    const ReadMyBadgeScan                     = '%s/summits/badge-scans/read/me';
    const WriteRegistrationData               = '%s/summits/registration/write';

    // bookable rooms
    const ReadBookableRoomsData               = '%s/bookable-rooms/read';
    const WriteMyBookableRoomsReservationData = '%s/bookable-rooms/my-reservations/write';
    const ReadMyBookableRoomsReservationData  = '%s/bookable-rooms/my-reservations/read';
    const BookableRoomsReservation            = '%s/bookable-rooms/reserve';
    const WriteBookableRoomsData              = '%s/bookable-rooms/write';

    const ReadNotifications                   = '%s/summits/read-notifications';
    const WriteNotifications                  = '%s/summits/write-notifications';

    const WriteSummitData                     = '%s/summits/write';
    const WriteSpeakersData                   = '%s/speakers/write';
    const ReadSpeakersData                    = '%s/speakers/read';
    const WriteTrackTagGroupsData             = '%s/track-tag-groups/write';
    const WriteTrackQuestionTemplateData      = '%s/track-question-templates/write';
    const WriteMySpeakersData                 = '%s/speakers/write/me';
    const ReadMySpeakersData                  = '%s/speakers/read/me';

    const PublishEventData                    = '%s/summits/publish-event';
    const WriteEventData                      = '%s/summits/write-event';
    const WriteVideoData                      = '%s/summits/write-videos';
    const WritePresentationVideosData         = '%s/summits/write-presentation-videos';
    const WritePresentationLinksData          = '%s/summits/write-presentation-links';
    const WritePresentationSlidesData         = '%s/summits/write-presentation-slides';
    const WritePresentationMaterialsData      = '%s/summits/write-presentation-materials';

    const WriteAttendeesData                  = '%s/attendees/write';
    const WritePromoCodeData                  = '%s/promo-codes/write';
    const WriteEventTypeData                  = '%s/event-types/write';
    const WriteTracksData                     = '%s/tracks/write';
    const WriteTrackGroupsData                = '%s/track-groups/write';
    const WriteLocationsData                  = '%s/locations/write';
    const WriteRSVPTemplateData               = '%s/rsvp-templates/write';
    const WriteLocationBannersData            = '%s/locations/banners/write';
    const WriteSummitSpeakerAssistanceData    = '%s/summit-speaker-assistance/write';
    const WriteTicketTypeData                 = '%s/ticket-types/write';
    const WritePresentationData               = '%s/summits/write-presentation';

    const WriteTagsData                       = '%s/tags/write';
    const ReadTagsData                        = '%s/tags/read';
}