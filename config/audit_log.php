<?php

return [
    'entities' => [
        \models\main\SummitMemberSchedule::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMemberScheduleAuditLogFormatter::class,
        ],
        \models\summit\PresentationSpeaker::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationSpeakerAuditLogFormatter::class,
        ],
        \models\summit\Presentation::class => [
            'enabled' => true,
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
            'strategy' => \App\Audit\ConcreteFormatters\SpeakerAssistanceAuditLogFormatter::class,
        ],
        \models\summit\SummitTrackChair::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitTrackChairAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationTrackChairRatingTypeAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationTrackChairScoreTypeAuditLogFormatter::class,
        ],
    ]
];
