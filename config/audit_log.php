<?php

return [
    'entities' => [
        \models\main\SummitMemberSchedule::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMemberScheduleAuditLogFormatter::class,
        ],
        \models\summit\PresentationSpeaker::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSpeakerAuditLogFormatter::class,
        ],
        \models\summit\Presentation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationUserSubmissionAuditLogFormatter::class,
            'strategies' => [
                [
                    'route' => 'POST|api/v1/summits/{id}/events',
                    'formatter' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationEventApiAuditLogFormatter::class,
                ],
                [
                    'route' => 'PUT|api/v1/summits/{id}/events/{event_id}',
                    'formatter' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationEventApiAuditLogFormatter::class,
                ],
                [
                    'route' => 'POST|api/v1/summits/{id}/presentations',
                    'formatter' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSubmissionAuditLogFormatter::class,
                ],
                [
                    'route' => 'PUT|api/v1/summits/{id}/presentations/{presentation_id}',
                    'formatter' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSubmissionAuditLogFormatter::class,
                ],
            ]
        ],
        \App\Models\Foundation\Summit\SelectionPlan::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SelectionPlanAuditLogFormatter::class,
        ],
        \models\summit\SpeakerRegistrationRequest::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SpeakerRegistrationRequestAuditLogFormatter::class,
        ],
        \models\summit\SummitSubmissionInvitation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SubmissionInvitationAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Speakers\FeaturedSpeaker::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\FeaturedSpeakerAuditLogFormatter::class,
        ],
        \models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSpeakerSummitAssistanceConfirmationAuditLogFormatter::class,
        ],
        \models\summit\SummitTrackChair::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitTrackChairAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationTrackChairRatingTypeAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationTrackChairScoreTypeAuditLogFormatter::class,
        ],
        \models\summit\Summit::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitAuditLogFormatter::class,
        ],
        \models\summit\SummitEvent::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitEventAuditLogFormatter::class,
        ],
        \models\summit\SummitGeoLocatedLocation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitGeoLocatedLocationAuditLogFormatter::class,
        ],
        \models\summit\PresentationVideo::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationVideoAuditLogFormatter::class,
        ],
        \models\summit\PresentationSlide::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSlideAuditLogFormatter::class,
        ],
        \models\summit\PresentationLink::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationLinkAuditLogFormatter::class,
        ],
        \models\summit\PresentationMediaUpload::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationMediaUploadAuditLogFormatter::class,
        ],
        \models\summit\PresentationActionType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationFormatters\PresentationActionTypeAuditLogFormatter::class,
        ],
        \models\summit\SummitEventAttendanceMetric::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitEventAttendanceMetricAuditLogFormatter::class,
        ],
        \models\summit\SummitMediaUploadType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMediaUploadTypeAuditLogFormatter::class,
        ],
        \models\summit\SummitVenue::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitVenueAuditLogFormatter::class,
        ],
        \models\summit\SummitExternalLocation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitExternalLocationAuditLogFormatter::class,
        ],
        \models\summit\SummitHotel::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitHotelAuditLogFormatter::class,
        ],
        \models\summit\SummitAirport::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitAirportAuditLogFormatter::class,
        ],
        \models\summit\SummitVenueRoom::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitVenueRoomAuditLogFormatter::class,
        ],
        \models\summit\SummitMetric::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMetricAuditLogFormatter::class,
        ],
        \models\summit\SummitSponsorship::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitSponsorshipAuditLogFormatter::class,
        ],
        \models\summit\SummitPresentationComment::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitPresentationCommentAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitProposedScheduleAllowedLocationAuditLogFormatter::class,
        ],
        \models\summit\SummitSelectedPresentation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitSelectedPresentationAuditLogFormatter::class,
        ],
        \models\summit\SummitBookableVenueRoomAttributeValue::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitBookableVenueRoomAttributeValueAuditLogFormatter::class,
        ],
        \models\summit\SummitSelectedPresentationList::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitSelectedPresentationListAuditLogFormatter::class,
        ],
    ]
];
